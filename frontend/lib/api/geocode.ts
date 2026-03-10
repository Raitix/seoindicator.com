export interface GeocodeResult {
  latitude: number;
  longitude: number;
  displayName?: string;
}

export async function geocodeAddress(address: string): Promise<GeocodeResult> {
  const query = address.trim();

  if (!query) {
    throw new Error("Address is required for geocoding");
  }

  const url = new URL("https://nominatim.openstreetmap.org/search");
  url.searchParams.set("q", query);
  url.searchParams.set("format", "jsonv2");
  url.searchParams.set("limit", "1");
  url.searchParams.set("addressdetails", "1");

  const response = await fetch(url.toString(), {
    headers: {
      Accept: "application/json",
    },
  });

  if (!response.ok) {
    throw new Error("Unable to geocode this address right now");
  }

  const results = (await response.json()) as Array<{
    lat?: string;
    lon?: string;
    display_name?: string;
  }>;

  const match = results?.[0];

  if (!match?.lat || !match?.lon) {
    throw new Error("No coordinates found for that address");
  }

  const latitude = Number(match.lat);
  const longitude = Number(match.lon);

  if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
    throw new Error("Geocoder returned invalid coordinates");
  }

  return {
    latitude,
    longitude,
    displayName: match.display_name,
  };
}
