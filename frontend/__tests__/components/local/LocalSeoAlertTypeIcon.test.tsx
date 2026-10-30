import React from "react";
import { render, screen } from "@testing-library/react";
import "@testing-library/jest-dom";
import { describe, expect, it } from "vitest";

import { LocalSeoAlertTypeIcon } from "@/components/local/LocalSeoAlertTypeIcon";
import { LOCAL_SEO_ALERT_TYPE_CONFIG, type LocalSeoAlertType } from "@/lib/types/location-alerts";

describe("LocalSeoAlertTypeIcon", () => {
  (Object.keys(LOCAL_SEO_ALERT_TYPE_CONFIG) as LocalSeoAlertType[]).forEach((type) => {
    it(`renders an accessible icon label for ${type}`, () => {
      render(<LocalSeoAlertTypeIcon type={type} />);

      expect(screen.getByLabelText(`${LOCAL_SEO_ALERT_TYPE_CONFIG[type].label} alert`)).toBeInTheDocument();
      expect(screen.getByTestId(`local-seo-alert-icon-${type}`)).toHaveAttribute(
        "title",
        LOCAL_SEO_ALERT_TYPE_CONFIG[type].iconLabel
      );
    });
  });
});
