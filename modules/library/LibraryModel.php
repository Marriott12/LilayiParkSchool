<?php
/**
 * Library Model
 * Manages library books and borrow records
 */

class LibraryModel extends BaseModel {
    protected $table = 'Books';
    protected $primaryKey = 'bookID';
    
    /**
     * Get all books with availability info
     */
    public function getAllWithAvailability($limit = null, $offset = null) {
        $sql = "SELECT b.*,
                       (b.totalCopies - b.availableCopies) as borrowedCopies
                FROM {$this->table} b
                WHERE b.isActive = 1
                ORDER BY b.title";
        
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Search books
     */
    public function search($searchTerm) {
        $sql = "SELECT b.*,
                       (b.totalCopies - b.availableCopies) as borrowedCopies
                FROM {$this->table} b
                WHERE b.isActive = 1
                AND (b.title LIKE ? OR b.author LIKE ? OR b.ISBN LIKE ? OR b.category LIKE ?)
                ORDER BY b.title";
        
        $searchPattern = "%{$searchTerm}%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get book with borrow history
     */
    public function getBookWithHistory($bookID) {
        $book = $this->find($bookID);
        
        if ($book) {
            $sql = "SELECT br.*, p.fName, p.sName,
                           u1.username as issuedByName,
                           u2.username as returnedToName
                    FROM BorrowRecords br
                    LEFT JOIN Pupil p ON br.pupilID = p.pupilID
                    LEFT JOIN Users u1 ON br.issuedBy = u1.userID
                    LEFT JOIN Users u2 ON br.returnedTo = u2.userID
                    WHERE br.bookID = ?
                    ORDER BY br.borrowDate DESC
                    LIMIT 10";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$bookID]);
            $book['history'] = $stmt->fetchAll();
        }
        
        return $book;
    }
    
    /**
     * Borrow a book
     */
    public function borrowBook($bookID, $pupilID, $dueDate, $issuedBy) {
        // Check if book is available
        $book = $this->find($bookID);
        if (!$book || $book['availableCopies'] < 1) {
            throw new Exception('Book is not available for borrowing');
        }
        
        // Check if student has reached max borrow limit
        $maxBooks = 3; // Could be from settings
        $sql = "SELECT COUNT(*) as count FROM BorrowRecords 
                WHERE pupilID = ? AND status = 'borrowed'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID]);
        $result = $stmt->fetch();
        
        if ($result['count'] >= $maxBooks) {
            throw new Exception('Student has reached maximum borrow limit');
        }
        
        // Create borrow record
        $sql = "INSERT INTO BorrowRecords 
                (bookID, pupilID, borrowDate, dueDate, issuedBy, status)
                VALUES (?, ?, CURDATE(), ?, ?, 'borrowed')";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$bookID, $pupilID, $dueDate, $issuedBy]);
        
        // Update available copies
        $this->db->prepare("UPDATE Books SET availableCopies = availableCopies - 1 WHERE bookID = ?")
                 ->execute([$bookID]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Return a book
     */
    public function returnBook($borrowID, $returnedTo, $fine = 0) {
        // Get borrow record
        $sql = "SELECT * FROM BorrowRecords WHERE borrowID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$borrowID]);
        $record = $stmt->fetch();
        
        if (!$record) {
            throw new Exception('Borrow record not found');
        }
        
        // Update borrow record
        $sql = "UPDATE BorrowRecords 
                SET returnDate = CURDATE(), 
                    returnedTo = ?,
                    fine = ?,
                    status = 'returned'
                WHERE borrowID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$returnedTo, $fine, $borrowID]);
        
        // Update available copies
        $this->db->prepare("UPDATE Books SET availableCopies = availableCopies + 1 WHERE bookID = ?")
                 ->execute([$record['bookID']]);
        
        return true;
    }
    
    /**
     * Get borrowed books by pupil
     */
    public function getBorrowedByPupil($pupilID) {
        $sql = "SELECT br.*, b.title, b.author, b.ISBN,
                       DATEDIFF(CURDATE(), br.dueDate) as daysOverdue
                FROM borrowrecords br
                LEFT JOIN books b ON br.bookID = b.bookID
                WHERE br.pupilID = ? AND br.status = 'borrowed'
                ORDER BY br.borrowDate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$pupilID]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get overdue books
     */
    public function getOverdueBooks() {
        $sql = "SELECT br.*, b.title, b.author, b.ISBN,
                p.fName as pupilFirstName, p.lName as pupilLastName, p.pupilID,
                DATEDIFF(CURDATE(), br.dueDate) as daysOverdue
                FROM borrowrecords br
                INNER JOIN books b ON br.bookID = b.bookID
                INNER JOIN pupil p ON br.pupilID = p.pupilID
                WHERE br.status = 'borrowed' AND br.dueDate < CURDATE()
                ORDER BY br.dueDate";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
