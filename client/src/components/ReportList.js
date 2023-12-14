import React, { useEffect, useState } from 'react';
import axios from 'axios';

const ReportList = () => {
  const [reports, setReports] = useState([]);

  useEffect(() => {
    // Fetch reports from the backend
    axios.get('http://localhost:3000/server.php')
    .then(response => {
        console.log('Full Response:', response.data.reports);
        setReports(response.data.reports);
      })
      .catch(error => console.error(error));
  }, []);

  const handleBlockContent = (reportId) => {
    axios({
      method: 'put',
      url: 'http://localhost:3000/server.php',
      data: {
        reportId: reportId,
        action: 'block'
      },
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      transformRequest: (data, headers) => {
        return Object.entries(data)
          .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
          .join('&');
      }
    })
    .then(response => {
      console.log(response.data);
    })
    .catch(error => console.error(error));
  };

  const handleResolveTicket = (reportId) => {
    axios({
      method: 'put',
      url: 'http://localhost:3000/server.php',
      data: {
        reportId: reportId,
        action: 'resolve'
      },
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded'
      },
      transformRequest: (data, headers) => {
        return Object.entries(data)
          .map(([key, value]) => `${encodeURIComponent(key)}=${encodeURIComponent(value)}`)
          .join('&');
      }
    })
    .then(response => {
      console.log(response.data);
    })
    .catch(error => console.error(error));
  }; 


  return (
    <div className='list-report'>
      <ul>
        {Array.isArray(reports) && reports.map(report => ( 
          <li key={report.id}>
            <div>
                <b>ID: </b>{report.id} | 
                <small><b> TYPE:</b> {report.referenceType}</small> | 
                <small><b> State: </b>{report.state}</small><br />
            </div>
            <button onClick={() => handleBlockContent(report.id)}>Block</button>
            <button onClick={() => handleResolveTicket(report.id)}>Resolve</button>
          </li>
        ))}
      </ul>
    </div>
  );
};

export default ReportList;
