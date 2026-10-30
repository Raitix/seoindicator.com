import { z } from "zod";

const keywordBaseSchema = {
  keyword: z
    .string()
    .trim()
    .min(1, "Keyword is required")
    .max(255, "Keyword must be 255 characters or fewer"),
  targetUrl: z
    .string()
    .trim()
    .url("Enter a valid URL")
    .max(2048, "URL is too long")
    .optional()
    .or(z.literal("")),
  searchEngine: z.enum(["google"]),
  location: z.enum(["us"]),
  device: z.enum(["desktop"]),
};

export const keywordCreateSchema = z.object(keywordBaseSchema).transform((data) => ({
  ...data,
  targetUrl: data.targetUrl ? data.targetUrl.trim() : undefined,
}));

export const keywordEditSchema = z
  .object({
    targetUrl: keywordBaseSchema.targetUrl,
    status: z.enum(["active", "paused"]),
  })
  .transform((data) => ({
    ...data,
    targetUrl: data.targetUrl ? data.targetUrl.trim() : undefined,
  }));

export type KeywordCreateValues = z.infer<typeof keywordCreateSchema>;
export type KeywordEditValues = z.infer<typeof keywordEditSchema>;
