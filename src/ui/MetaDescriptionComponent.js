import React from "react";

const MetaDescriptionComponent = ({ metaDescription, error }) => {
  return (
    <div className="meta-description-container">
      {error ? (
        <p className="error-message">{error}</p>
      ) : (
        <p className="meta-description-result">{metaDescription}</p>
      )}
    </div>
  );
};

export default MetaDescriptionComponent;