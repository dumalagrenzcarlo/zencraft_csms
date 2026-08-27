// api.browser.js (ES module for browser)
export async function validateToken(token, baseUrl) {
  const response = await fetch(`${baseUrl}/api/validatetoken?token=${token}`);
  if (!response.ok) throw new Error(`HTTP error! Status: ${response.status}`);
  return await response.json();
}