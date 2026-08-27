const DESKTOP_UPDATE_PATH = "api/application/desktop-updates/";

function buildUpdateFeedUrl(serverUrl) {
  if (typeof serverUrl !== "string" || serverUrl.trim() === "") {
    throw new TypeError("A Server URL is required to configure automatic updates.");
  }

  const url = new URL(serverUrl.trim());
  if (url.protocol !== "http:" && url.protocol !== "https:") {
    throw new TypeError("The Server URL must use HTTP or HTTPS.");
  }

  url.search = "";
  url.hash = "";
  url.pathname = `${url.pathname.replace(/\/+$/, "")}/${DESKTOP_UPDATE_PATH}`;

  return url.href;
}

module.exports = { buildUpdateFeedUrl };
