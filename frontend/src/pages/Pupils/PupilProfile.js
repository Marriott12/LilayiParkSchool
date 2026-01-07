import React from 'react';
import { useNavigate, useParams } from 'react-router-dom';

const PupilProfile = () => {
  const { id } = useParams();
  const navigate = useNavigate();

  return (
    <div>
      <h1 className="text-3xl font-bold text-gray-800 mb-6">
        Pupil Profile - {id}
      </h1>
      <div className="bg-white rounded-lg shadow-lg p-6">
        <p className="text-gray-600">Detailed pupil profile with photo, classes, payments, and attendance</p>
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

export default PupilProfile;
