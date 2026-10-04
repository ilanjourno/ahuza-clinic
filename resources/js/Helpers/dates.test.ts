import { test } from "node:test";
import assert from "node:assert/strict";
import { formatDate, parseDate } from "./dates.ts";

test("parseDate reads SQL and ISO timestamps", () => {
    assert.equal(parseDate("2025-02-23 17:18:35")?.toISODate(), "2025-02-23");
    assert.equal(
        parseDate("2025-02-23T17:18:35.000000Z")?.toISODate(),
        "2025-02-23"
    );
    assert.equal(parseDate(null), null);
    assert.equal(parseDate("not a date"), null);
});

test("formatDate localises the date", () => {
    assert.equal(formatDate("2025-02-23 17:18:35", "fr"), "23 février 2025");
    assert.equal(formatDate("2025-02-23 17:18:35", "en"), "February 23, 2025");
    assert.equal(formatDate(undefined, "fr"), null);
});
