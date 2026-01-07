import React from 'react';
import { useNavigate } from 'react-router-dom';

const PupilForm = () => {
  const navigate = useNavigate();

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-6">
        Pupil Form
      </h1>
      <div className="bg-white rounded-lg shadow-lg p-6">
        <p className="text-gray-600">Pupil form implementation - Add/Edit pupil with all required fields</p>
        <button
          onClick={() => navigate('/pupils')}
          className="mt-4 bg-gray-500 text-white px-4 py-2 rounded-lg hover:bg-gray-600"
        >
          Back to Pupils
        </button>
      </div>
    </div>
  );
};

export default PupilForm;
