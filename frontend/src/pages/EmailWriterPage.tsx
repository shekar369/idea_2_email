import React from 'react';
import EmailWriterCore from '../components/EmailWriterCore'; // Import the core component

const EmailWriterPage: React.FC = () => {
  // This page will act as a wrapper for the EmailWriterCore component.
  // It can also include any page-specific layout or context if needed in the future.

  // For now, it simply renders the core email writing UI.
  // The EmailWriterCore component will need to be updated to use the new API service
  // and remove direct calls like `window.claude.complete`.

  return (
    <div>
      {/* <h1>Email Writer Dashboard</h1> */}
      {/* <p>This is where the main email writing application will reside.</p> */}
      <EmailWriterCore />
    </div>
  );
};

export default EmailWriterPage;
