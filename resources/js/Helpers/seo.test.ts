import { test } from "node:test";
import assert from "node:assert/strict";
import { escapeAttr, metaDescription, ogLocale, pageTitle } from "./seo.ts";

test("escapeAttr neutralises characters that break an HTML attribute", () => {
    assert.equal(
        escapeAttr('Le "DIU" & <vous>'),
        "Le &quot;DIU&quot; &amp; &lt;vous&gt;"
    );
});

test("pageTitle appends the brand only when the result fits in 60 characters", () => {
    assert.equal(
        pageTitle("Ménopause", "Dr Claude Allouche", "Accueil"),
        "Ménopause - Dr Claude Allouche"
    );
    const long = "Métrorragies aux 2e et 3e trimestres de grossesse : que faire ?";
    assert.equal(pageTitle(long, "Dr Claude Allouche", "Accueil"), long);
    assert.equal(pageTitle(undefined, "Dr Claude Allouche", "Accueil"), "Accueil");
});

test("metaDescription collapses whitespace and cuts on a word boundary", () => {
    assert.equal(metaDescription("  Court   texte. "), "Court texte.");
    const cut = metaDescription("mot ".repeat(60));
    assert.ok(cut.length <= 155, `length ${cut.length}`);
    assert.ok(cut.endsWith("mot…"));
});

test("ogLocale maps site locales to Open Graph locales", () => {
    assert.equal(ogLocale("fr"), "fr_FR");
    assert.equal(ogLocale("en"), "en_US");
    assert.equal(ogLocale("he"), "he_IL");
});
