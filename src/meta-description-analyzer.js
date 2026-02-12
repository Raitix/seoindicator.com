function analyzeMetaDescription(description) {
  if (!description) {
    return 'Description is empty. Please provide a meta description.';
  }

  const length = description.length;

  if (length < 50) {
    return 'Meta description is too short. Add more content for better SEO.';
  } else if (length > 160) {
    return 'Meta description is too long. Reduce the length to be within 50-160 characters.';
  }

  return 'Your meta description length is perfect. Ensure it aligns with your page content.';
}

module.exports = analyzeMetaDescription;