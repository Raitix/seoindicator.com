"use client";

import * as React from "react";
import { zodResolver } from "@hookform/resolvers/zod";
import { AlertCircle, Building2, Globe, Loader2, MapPin, Phone } from "lucide-react";
import { useForm } from "react-hook-form";
import { z } from "zod";
import { toast } from "sonner";

import { Button } from "@/components/ui/button";
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import { geocodeAddress, type GeocodeResult } from "@/lib/api/geocode";
import type { BusinessLocation, LocationMutationPayload } from "@/lib/types/locations";
import { cn } from "@/lib/utils";

const categories = [
  "Restaurant",
  "Retail Store",
  "Professional Services",
  "Healthcare",
  "Automotive",
  "Hospitality",
  "Other",
] as const;

const phonePattern = /^[\d\s+().-]+$/;

const normalizeWebsite = (value?: string | null) => {
  const trimmed = value?.trim() ?? "";

  if (!trimmed) {
    return null;
  }

  if (/^https?:\/\//i.test(trimmed)) {
    return trimmed;
  }

  return `https://${trimmed}`;
};

const isSafeWebsite = (value?: string | null) => {
  const normalized = normalizeWebsite(value);

  if (!normalized) {
    return true;
  }

  try {
    const url = new URL(normalized);
    return url.protocol === "http:" || url.protocol === "https:";
  } catch {
    return false;
  }
};

const locationFormSchema = z.object({
  name: z
    .string()
    .trim()
    .min(1, "Location name is required")
    .max(255, "Location name must be 255 characters or fewer")
    .refine((value) => !/[<>]/.test(value), "Location name cannot contain HTML-like characters"),
  address: z
    .string()
    .trim()
    .min(1, "Address is required")
    .max(512, "Address must be 512 characters or fewer")
    .refine((value) => !/[<>]/.test(value), "Address cannot contain HTML-like characters"),
  phone: z
    .string()
    .trim()
    .max(50, "Phone number must be 50 characters or fewer")
    .refine((value) => value.length === 0 || phonePattern.test(value), "Please enter a valid phone number")
    .optional()
    .default(""),
  website: z
    .string()
    .trim()
    .max(2048, "Website must be 2048 characters or fewer")
    .refine((value) => value.length === 0 || isSafeWebsite(value), "Please enter a valid website URL")
    .optional()
    .default(""),
  category: z.string().trim().max(100, "Category must be 100 characters or fewer").optional().default(""),
});

export type LocationFormValues = z.infer<typeof locationFormSchema>;

export interface LocationFormProps {
  open: boolean;
  mode: "add" | "edit";
  location?: BusinessLocation | null;
  isSubmitting?: boolean;
  onClose: () => void;
  onSubmit: (payload: LocationMutationPayload) => Promise<void> | void;
}

const toDefaultValues = (location?: BusinessLocation | null): LocationFormValues => ({
  name: location?.name ?? "",
  address: location?.address ?? "",
  phone: location?.phone ?? "",
  website: location?.website ?? "",
  category: location?.category ?? "",
});

const toInitialCoordinates = (location?: BusinessLocation | null): GeocodeResult | null => {
  if (location?.latitude === null || location?.latitude === undefined || location?.longitude === null || location?.longitude === undefined) {
    return null;
  }

  return {
    latitude: location.latitude,
    longitude: location.longitude,
    displayName: location.address,
  };
};

export function LocationForm({
  open,
  mode,
  location,
  isSubmitting = false,
  onClose,
  onSubmit,
}: LocationFormProps) {
  const initialCoordinates = React.useMemo(() => toInitialCoordinates(location), [location]);
  const [coordinates, setCoordinates] = React.useState<GeocodeResult | null>(initialCoordinates);
  const [lastGeocodedAddress, setLastGeocodedAddress] = React.useState(location?.address?.trim() ?? "");
  const [isGeocoding, setIsGeocoding] = React.useState(false);
  const [geocodeError, setGeocodeError] = React.useState<string | null>(null);
  const [addressDirtySinceGeocode, setAddressDirtySinceGeocode] = React.useState(false);
  const submissionGuardRef = React.useRef(false);

  const {
    register,
    handleSubmit,
    watch,
    reset,
    formState: { errors },
  } = useForm<LocationFormValues>({
    resolver: zodResolver(locationFormSchema),
    defaultValues: toDefaultValues(location),
  });

  const addressRegistration = register("address");
  const watchedAddress = watch("address");

  React.useEffect(() => {
    if (!open) {
      return;
    }

    reset(toDefaultValues(location));
    const nextCoordinates = toInitialCoordinates(location);
    setCoordinates(nextCoordinates);
    setLastGeocodedAddress(location?.address?.trim() ?? "");
    setGeocodeError(null);
    setAddressDirtySinceGeocode(false);
    submissionGuardRef.current = false;
  }, [location, open, reset]);

  React.useEffect(() => {
    const trimmed = watchedAddress?.trim() ?? "";
    const geocoded = lastGeocodedAddress.trim();

    if (!trimmed) {
      setCoordinates(null);
      setAddressDirtySinceGeocode(false);
      return;
    }

    const changed = Boolean(geocoded) ? trimmed !== geocoded : trimmed.length > 0;
    setAddressDirtySinceGeocode(changed);

    if (changed && coordinates) {
      setCoordinates(null);
    }
  }, [coordinates, lastGeocodedAddress, watchedAddress]);

  const resolveCoordinates = React.useCallback(
    async (address: string, force = false) => {
      const trimmedAddress = address.trim();

      if (!trimmedAddress) {
        setCoordinates(null);
        setGeocodeError(null);
        setLastGeocodedAddress("");
        return null;
      }

      if (!force && !addressDirtySinceGeocode && coordinates && trimmedAddress === lastGeocodedAddress) {
        return coordinates;
      }

      setIsGeocoding(true);
      setGeocodeError(null);

      try {
        const result = await geocodeAddress(trimmedAddress);
        setCoordinates(result);
        setLastGeocodedAddress(trimmedAddress);
        setAddressDirtySinceGeocode(false);
        return result;
      } catch (error) {
        const message = error instanceof Error ? error.message : "Unable to geocode this address right now";
        setGeocodeError(message);
        return null;
      } finally {
        setIsGeocoding(false);
      }
    },
    [addressDirtySinceGeocode, coordinates, lastGeocodedAddress]
  );

  const handleAddressBlur = async (event: React.FocusEvent<HTMLInputElement>) => {
    addressRegistration.onBlur(event);

    const trimmed = event.target.value.trim();
    if (!trimmed) {
      return;
    }

    await resolveCoordinates(trimmed);
  };

  const submitHandler = handleSubmit(async (values) => {
    if (submissionGuardRef.current || isSubmitting) {
      return;
    }

    submissionGuardRef.current = true;

    try {
      const trimmedAddress = values.address.trim();
      let nextCoordinates = coordinates;

      if (trimmedAddress && (addressDirtySinceGeocode || !coordinates)) {
        nextCoordinates = await resolveCoordinates(trimmedAddress, true);

        if (!nextCoordinates) {
          toast.error("We could not confirm coordinates for that address yet.");
          return;
        }
      }

      await onSubmit({
        name: values.name.trim(),
        address: trimmedAddress,
        phone: values.phone?.trim() ? values.phone.trim() : null,
        website: normalizeWebsite(values.website),
        category: values.category?.trim() ? values.category.trim() : null,
        latitude: nextCoordinates?.latitude ?? null,
        longitude: nextCoordinates?.longitude ?? null,
      });
    } finally {
      submissionGuardRef.current = false;
    }
  });

  return (
    <Dialog open={open} onOpenChange={(nextOpen) => (!nextOpen && !isSubmitting && !isGeocoding ? onClose() : undefined)}>
      <DialogContent
        className="max-h-[90vh] overflow-y-auto border-slate-800 bg-slate-950 text-slate-100 sm:max-w-xl"
        aria-describedby="location-form-description"
      >
        <DialogHeader>
          <DialogTitle className="text-white">
            {mode === "add" ? "Add new location" : "Edit location"}
          </DialogTitle>
          <DialogDescription id="location-form-description" className="text-slate-400">
            Capture the core business details used for Local SEO tracking and GBP performance monitoring.
          </DialogDescription>
        </DialogHeader>

        <form className="space-y-5" onSubmit={submitHandler}>
          <div className="grid gap-5 sm:grid-cols-2">
            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="location-name">Location name *</Label>
              <div className="relative">
                <Building2 className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" aria-hidden="true" />
                <Input
                  id="location-name"
                  data-testid="name-input"
                  placeholder="Main office"
                  aria-invalid={Boolean(errors.name)}
                  className="border-slate-700 bg-slate-900 pl-10 text-slate-100"
                  {...register("name")}
                />
              </div>
              {errors.name ? <p className="text-sm text-red-400">{errors.name.message}</p> : null}
            </div>

            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="location-address">Address *</Label>
              <div className="relative">
                <MapPin className="pointer-events-none absolute left-3 top-3.5 h-4 w-4 text-slate-500" aria-hidden="true" />
                <Input
                  id="location-address"
                  data-testid="address-input"
                  placeholder="123 Main Street, City, State ZIP"
                  aria-invalid={Boolean(errors.address)}
                  className="border-slate-700 bg-slate-900 pl-10 text-slate-100"
                  {...addressRegistration}
                  onBlur={handleAddressBlur}
                />
              </div>
              <div className="flex flex-wrap items-center gap-3">
                <Button
                  type="button"
                  variant="outline"
                  className="border-slate-700 bg-slate-900 text-slate-100 hover:bg-slate-800"
                  disabled={isGeocoding || !watchedAddress?.trim()}
                  onClick={async () => {
                    const result = await resolveCoordinates(watchedAddress ?? "", true);
                    if (result) {
                      toast.success("Coordinates updated");
                    } else if (watchedAddress?.trim()) {
                      toast.error("Unable to geocode that address right now");
                    }
                  }}
                >
                  {isGeocoding ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" /> : <MapPin className="h-4 w-4" aria-hidden="true" />}
                  {coordinates && !addressDirtySinceGeocode ? "Refresh coordinates" : "Geocode address"}
                </Button>

                {addressDirtySinceGeocode ? (
                  <span className="text-xs text-amber-300">Address changed — coordinates will be refreshed on save.</span>
                ) : null}
              </div>
              {errors.address ? <p className="text-sm text-red-400">{errors.address.message}</p> : null}
              {geocodeError ? (
                <div className="flex items-start gap-2 rounded-lg border border-amber-500/30 bg-amber-500/10 px-3 py-2 text-sm text-amber-100">
                  <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" aria-hidden="true" />
                  <span>{geocodeError}</span>
                </div>
              ) : null}
              {coordinates ? (
                <div className="rounded-xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-100">
                  <div className="font-medium">Coordinates confirmed</div>
                  <div className="mt-1 text-emerald-50/90">
                    {coordinates.latitude.toFixed(6)}, {coordinates.longitude.toFixed(6)}
                  </div>
                  {coordinates.displayName ? (
                    <div className="mt-1 text-xs text-emerald-100/80">{coordinates.displayName}</div>
                  ) : null}
                </div>
              ) : null}
            </div>

            <div className="space-y-2">
              <Label htmlFor="location-phone">Phone number</Label>
              <div className="relative">
                <Phone className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" aria-hidden="true" />
                <Input
                  id="location-phone"
                  data-testid="phone-input"
                  placeholder="+1 (555) 123-4567"
                  aria-invalid={Boolean(errors.phone)}
                  className="border-slate-700 bg-slate-900 pl-10 text-slate-100"
                  {...register("phone")}
                />
              </div>
              {errors.phone ? <p className="text-sm text-red-400">{errors.phone.message}</p> : null}
            </div>

            <div className="space-y-2">
              <Label htmlFor="location-website">Website</Label>
              <div className="relative">
                <Globe className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-500" aria-hidden="true" />
                <Input
                  id="location-website"
                  data-testid="website-input"
                  placeholder="https://example.com"
                  aria-invalid={Boolean(errors.website)}
                  className="border-slate-700 bg-slate-900 pl-10 text-slate-100"
                  {...register("website")}
                />
              </div>
              {errors.website ? <p className="text-sm text-red-400">{errors.website.message}</p> : null}
            </div>

            <div className="space-y-2 sm:col-span-2">
              <Label htmlFor="location-category">Category</Label>
              <select
                id="location-category"
                data-testid="category-select"
                className={cn(
                  "flex h-10 w-full rounded-md border border-slate-700 bg-slate-900 px-3 py-2 text-sm text-slate-100 outline-none transition",
                  "focus:border-blue-500 focus:ring-2 focus:ring-blue-500/40"
                )}
                {...register("category")}
              >
                <option value="">Select a category</option>
                {categories.map((category) => (
                  <option key={category} value={category}>
                    {category}
                  </option>
                ))}
              </select>
              {errors.category ? <p className="text-sm text-red-400">{errors.category.message}</p> : null}
            </div>
          </div>

          <DialogFooter className="gap-2 border-t border-slate-800 pt-4">
            <Button
              type="button"
              variant="outline"
              className="border-slate-700 bg-transparent text-slate-100 hover:bg-slate-900"
              disabled={isSubmitting || isGeocoding}
              onClick={onClose}
            >
              Cancel
            </Button>
            <Button type="submit" disabled={isSubmitting || isGeocoding}>
              {isSubmitting ? <Loader2 className="h-4 w-4 animate-spin" aria-hidden="true" /> : null}
              {isSubmitting ? "Saving..." : mode === "add" ? "Add location" : "Save changes"}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  );
}

export default LocationForm;
