// Backend AI Analysis for Meta Descriptions
const analyzeMetaDescription = (pageHTML) => {
  const metaDescriptionRegex = /<meta\s+name=["']description["']\s+content=["']([^"']+)["']/i;
  const match = pageHTML.match(metaDescriptionRegex);

  if (match && match[1]) {
    return { success: true, metaDescription: match[1] };
  }

  return { success: false, error: "Meta description not found" };
};

module.exports = analyzeMetaDescription;