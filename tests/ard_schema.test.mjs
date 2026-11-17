// Validates ard.json against the official ARD v0.91 entry schema (vendored from
// https://github.com/ards-project/ard-spec/blob/main/spec/schemas/ard-entry.schema.json, retrieved 2026-11-17)
// and applies the Lighthouse best-practice checks. Run: node --test tests/ard_schema.test.mjs
import { test } from "node:test";
import assert from "node:assert/strict";
import { readFileSync } from "node:fs";
import { createRequire } from "node:module";
const require = createRequire(new URL("../gomo-mcp/package.json", import.meta.url));
const Ajv2020 = require("ajv/dist/2020").default;
const addFormats = require("ajv-formats");
const read = path => JSON.parse(readFileSync(new URL(path, import.meta.url), "utf8"));
const schema = read("./schemas/ard-entry.schema.json");
const ajv = new Ajv2020({ strict: false, allErrors: true });
addFormats(ajv);
ajv.addSchema(schema);
const validateManifest = ajv.getSchema(`${schema.$id}#/$defs/ArdManifest`);

for (const file of ["../ard.json", "../gomo-api/ard.json"]) {
  test(`${file} conforms to the ARD manifest schema`, () => {
    const doc = read(file);
    assert.equal(validateManifest(doc), true, JSON.stringify(validateManifest.errors, null, 1));
  });
}

test("ard.json passes Lighthouse best-practice checks (no warnings)", () => {
  const doc = read("../ard.json");
  assert.equal("collections" in doc, false, "top-level collections is deprecated");
  for (const entry of doc.entries) {
    const q = entry.representativeQueries;
    assert(Array.isArray(q) && q.length >= 2 && q.length <= 5 && q.every(x => typeof x === "string"), `${entry.displayName}: 2-5 representative queries`);
    assert.match(entry.type, /^application\/[a-z0-9.+-]+$/, `${entry.displayName}: media type`);
    assert.notEqual(entry.type, "application/mcp-server+json", "deprecated MCP media type");
    if (entry.capabilities) assert(entry.capabilities.every(x => typeof x === "string"));
  }
});

test("schema validator rejects a broken manifest", () => {
  const broken = { entries: [{ identifier: "not-a-urn", displayName: "x", type: "application/json", url: "https://a.example", data: {} }] };
  assert.equal(validateManifest(broken), false);
});
