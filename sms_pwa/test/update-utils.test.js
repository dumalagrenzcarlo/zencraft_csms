const test = require("node:test");
const assert = require("node:assert/strict");

const { buildUpdateFeedUrl } = require("../src/update-utils");

test("builds the desktop update feed from the configured Server URL", () => {
  assert.equal(
    buildUpdateFeedUrl("https://school.example.com"),
    "https://school.example.com/api/application/desktop-updates/"
  );
});

test("preserves a Server URL subdirectory and removes query data", () => {
  assert.equal(
    buildUpdateFeedUrl("https://example.com/sms/?tenant=one#settings"),
    "https://example.com/sms/api/application/desktop-updates/"
  );
});

test("rejects non-web Server URLs", () => {
  assert.throws(() => buildUpdateFeedUrl("file:///tmp/server"), /HTTP or HTTPS/);
});
