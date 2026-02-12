import React from "react";
import { render, screen } from "@testing-library/react";
import MetaDescriptionComponent from "../src/ui/MetaDescriptionComponent";
import "@testing-library/jest-dom";

describe("MetaDescriptionComponent", () => {
  it("should render the meta description result when meta description exists", () => {
    render(<MetaDescriptionComponent metaDescription="This is a sample meta description." error={null} />);

    const result = screen.getByText(/This is a sample meta description\./i);
    expect(result).toBeInTheDocument();
  });

  it("should render an error message when error exists", () => {
    render(<MetaDescriptionComponent metaDescription={null} error="Meta description not found" />);

    const error = screen.getByText(/Meta description not found/i);
    expect(error).toBeInTheDocument();
  });
});