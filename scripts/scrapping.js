import puppeteer from "puppeteer";
import fs from "fs";
import path from "path";

(async () => {
    // Cookies de session Facebook, à fournir via l'environnement :
    // FB_C_USER=... FB_XS=... node scripts/scrapping.js
    const { FB_C_USER, FB_XS } = process.env;

    if (!FB_C_USER || !FB_XS) {
        console.error("Les variables FB_C_USER et FB_XS sont requises.");
        process.exit(1);
    }

    // Configuration des cookies
    const cookies = [
        {
            name: "c_user",
            value: FB_C_USER,
            domain: ".facebook.com",
            path: "/",
            httpOnly: false,
            secure: true,
            sameSite: "None",
        },
        {
            name: "xs",
            value: FB_XS,
            domain: ".facebook.com",
            path: "/",
            httpOnly: true,
            secure: true,
            sameSite: "None",
        },
        // Ajouter d'autres cookies si nécessaire
    ];

    // Lancer le navigateur
    const browser = await puppeteer.launch({ headless: false });
    const page = await browser.newPage();

    // Définir les cookies
    await page.setCookie(...cookies);

    // Naviguer vers Facebook
    await page.goto("https://www.facebook.com/DrAllouche2020/photos", {
        waitUntil: "networkidle2",
    });

    let collectedLinks = new Set();
    let previousHeight = 0;
    let scrollCounter = 0;

    while (true) {
        const newLinks = await page.evaluate(() => {
            const links = [];
            const anchors = document.querySelectorAll(
                ".x1i10hfl.xjbqb8w.x1ejq31n.xd10rxx.x1sy0etr.x17r0tee.x972fbf.xcfux6l.x1qhh985.xm0m39n.x9f619.x1ypdohk.xe8uvvx.xdj266r.x11i5rnm.xat24cr.x1mh8g0r.xexx8yu.x4uap5.x18d9i69.xkhd6sd.x16tdsg8.x1hl2dhg.xggy1nq.x1a2a7pz.x1heor9g.x1sur9pj.xkrqix3.x1lliihq.x5yr21d.x1n2onr6.xh8yej3"
            );
            anchors.forEach((anchor) => {
                if (anchor.href) {
                    links.push(anchor.href);
                }
            });
            return links;
        });

        const initialSize = collectedLinks.size;
        newLinks.forEach((link) => collectedLinks.add(link));

        if (collectedLinks.size === initialSize) {
            console.log("Aucun nouveau lien trouvé. Arrêt du script.");
            break;
        }

        await page.evaluate(() => window.scrollBy(0, window.innerHeight));
        await new Promise((resolve) => setTimeout(resolve, 2000));

        const newHeight = await page.evaluate("document.body.scrollHeight");
        if (newHeight === previousHeight) {
            scrollCounter++;
            if (scrollCounter > 5) break;
        } else {
            scrollCounter = 0;
            previousHeight = newHeight;
        }
    }

    console.log(`Nombre total de liens collectés: ${collectedLinks.size}`);

    const resultsFilePath = "./scripts/results.json";

    // Charger les résultats existants si le fichier existe
    let results = [];
    if (fs.existsSync(resultsFilePath)) {
        const data = fs.readFileSync(resultsFilePath);
        results = JSON.parse(data);
    }

    for (const link of collectedLinks) {
        console.log(`Visiting: ${link}`);
        await page.goto(link, { waitUntil: "networkidle2" });

        // Récupérer l'image
        const imageUrl = await page.evaluate(() => {
            const img = document.querySelector(
                ".x85a59c.x193iq5w.x4fas0m.x19kjcj4"
            );
            return img ? img.src : null;
        });

        // Récupérer le texte
        const textContent = await page.evaluate(async () => {
            const btn = document.querySelector(
                "div.x1i10hfl.xjbqb8w.x1ejq31n.xd10rxx.x1sy0etr.x17r0tee.x972fbf.xcfux6l.x1qhh985.xm0m39n.x9f619.x1ypdohk.xt0psk2.xe8uvvx.xdj266r.x11i5rnm.xat24cr.x1mh8g0r.xexx8yu.x4uap5.x18d9i69.xkhd6sd.x16tdsg8.x1hl2dhg.xggy1nq.x1a2a7pz.x1sur9pj.xkrqix3.xzsf02u.x1s688f[role=button]"
            );
            if (btn) {
                await btn.click();
                const textContent = document.querySelector(
                    "div.xyinxu5.x4uap5.x1g2khh7.xkhd6sd > span"
                );

                return textContent.innerText;
            }
        });

        // Passer au lien suivant si le texte est vide
        if (!textContent) {
            console.log(
                `Aucun texte trouvé pour ${link}. Passage au lien suivant.`
            );
            continue;
        }

        // Attendre que le texte soit chargé
        await new Promise((resolve) => setTimeout(resolve, 2000));

        // Ajouter les résultats
        const result = { link, imageUrl, textContent };
        results.push(result);

        // Mettre à jour le fichier JSON
        fs.writeFileSync(resultsFilePath, JSON.stringify(results, null, 2));
        console.log(`Résultat ajouté pour ${link}`);
    }

    console.log(`Final Results stored in ${resultsFilePath}`);

    // Fermer le navigateur
    await browser.close();
})();
