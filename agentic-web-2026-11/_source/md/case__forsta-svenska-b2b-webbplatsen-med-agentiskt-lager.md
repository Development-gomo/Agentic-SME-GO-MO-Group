Agentic Web \| Lästid: 5 minuter \| oktober 2026

# Den första svenska B2B-webbplatsen med ett  komplett agentiskt lager

I början av oktober 2026 möttes AI-agenter av en webbplats som enbart var byggd för människor. I dag finns ett särskilt agentiskt lager med lättillgängliga textfiler, strukturerad produktdata, ett publikt API och en MCP-server. Det gör att AI-agenter kan läsa produktkatalogen, hänvisa till informationen korrekt och skicka offertförfrågningar direkt till säljteamets befintliga arbetsflöde.

_Kunden är en svensk B2B-tillverkare av teknisk specialutrustning. Företagets namn utelämnas av sekretesskäl._

3/3

kontroller i Agentic Browsing

5/5

AI-plattformar: agentvänlig

7/7

agentkomponenter hittade

2/2

agentleads levererade

3/3

kontroller i Google Agentic Browsing godkända, upp från 1/3

5/5

AI-plattformar bedömde webbplatsen som agentvänlig

7/7

agentkomponenter hittade av alla plattformar

2/2

agentleads levererade i ett skarpt test

**Agentic Browsing:** Googles kategori för agentberedskap i PageSpeed Insights.

Om kunden

## En nischad B2B-tillverkare som säljer via offertkorg

Kunden är en svensk B2B-tillverkare av teknisk specialutrustning som säljer till teknikdrivna företag över hela världen. Det finns inga publika priser: varje affär börjar med en offertförfrågan.

Webbplatsen bygger på WordPress, med produktkatalogen i WooCommerce. Köpare konfigurerar produkter, lägger dem i en offertkorg och skickar korgen som en offertförfrågan till säljteamet. Webbplatsen fungerade bra för människor. Det som saknades var ett lager byggt för AI-agenter.

Möjlighet

## Bli en pålitlig informationskälla för AI-agenter, och gör det möjligt för dem att agera

Ingenjörer börjar allt oftare sin leverantörsresearch i ChatGPT, Claude, Perplexity eller en agentisk webbläsare. Assistenten läser några källor och skriver ett enda svar. Om den inte kan läsa en tillverkares specifikationer ordentligt gissar den, citerar en återförsäljare eller utelämnar tillverkaren.

Vid starten klarade webbplatsen 1 av 3 kontroller i Googles granskning Agentic Browsing. Tillgänglighetsträdet var inte korrekt uppbyggt, och det fanns ingen llms.txt som följde rekommendationerna. En AI-agent kunde läsa sidorna, men saknade ett strukturerat sätt att förstå produktkatalogen och en säker metod för att skicka leads.

Tillsammans med kunden satte vi upp tre mål.

**Läsbar:** AI-agenter ska kunna ta del av produktinformation, specifikationer och företagsuppgifter i ett tydligt, strukturerat format.

**Citerbar:** Ledande AI-plattformar ska kunna återge korrekt information om företaget och dess produkter.

**Agerbar:** AI-agenter ska, med köparens uttryckliga samtycke, kunna skicka offertförfrågningar och andra förfrågningar direkt till det befintliga säljflödet.

Allt detta skulle genomföras utan att bygga om den befintliga webbplatsen.

Leveranser · det agentiska lagret, nivå för nivå

Arbetet inleddes med en analys av webbplatsens beredskap för AI-agenter

03 · Agera

Utföra handlingar åt köparen

Skicka leads, med samtycke

MCP-serververktyget submit\_inquiryInsamling av samtycke

02 · Förstå

Förstå katalogen

Korrekt produktdata i stället för gissningar

schema.org-markupAPI-katalogOpenAPI-beskrivningPublikt REST-API (skrivskyddat)

01 · Läsa

Hitta och läsa

Tillförlitlig information från originalkällan, samlad på ett ställe

llms.txt/agent-sidarobots.txtai.txtÅtgärder i tillgänglighetsträdet

Grund: den befintliga webbplatsen i WordPress + WooCommerce, oförändrad

Strategi

## Tre lager. Läsa, förstå, agera.

Vi byggde det agentiska lagret i tre delar. Varje del svarar på en fråga som en AI-agent ställer när den kommer till en webbplats.

Tre lager, en agentisk webb

Lager 01 · Läsa

Hitta och läsa · llms.txt, /agent-sida, robots.txt, ai.txt

Google Agentic Browsing: 3/3

Lager 02 · Förstå

Förstå katalogen · schema.org, OpenAPI, REST-API

Hittades av alla fem AI-plattformar

Lager 03 · Agera

Utföra handlingar åt köparen · MCP-server, submit\_inquiry

En riktig offertförfrågan skickades och togs emot

Alla tre lagren ligger bredvid den befintliga WordPress-webbplatsen.

Lager 01 · Läsa

L

### Hitta och läsa

AI-agenter hittar tillförlitlig information samlad på ett ställe

Före

AI-agenter behövde tolka sidor utformade för människor, och webbplatsen saknade llms.txt.

Det här byggde vi

llms.txt, en /agent-sida, genomtänkta regler i robots.txt och ai.txt samt åtgärder i tillgänglighetsträdet.

Resultat

Google Agentic Browsing: 3/3

Lager 02 · Förstå

F

### Förstå katalogen

Korrekt produktdata i stället för gissningar

Före

Produktspecifikationerna var inbäddade i webbplatsens sidstruktur.

Det här byggde vi

schema.org-markup, en API-katalog med OpenAPI-beskrivning och ett publikt, skrivskyddat REST-API.

Resultat

Hittades av alla fem AI-plattformar

Lager 03 · Agera

A

### Utföra handlingar åt köparen

Offertförfrågningar och förfrågningar via MCP

Före

Leads kunde tidigare bara skickas via ett webbformulär för vanliga besökare, skyddat av reCAPTCHA.

Det här byggde vi

En MCP-server med verktyget submit\_inquiry, kopplad till WooCommerce och kontaktformuläret.

Resultat

En riktig offertförfrågan skickades och togs emot

Alla tre lagren ligger bredvid den befintliga WordPress-webbplatsen. Webbplatsen för människor byggdes inte om.

Genomförande

## Från att läsa webbplatsen till att skicka en offertförfrågan

Många satsningar på att anpassa webbplatser för AI-agenter fokuserar enbart på att göra informationen läsbar. Vi tog det ett steg längre. MCP-servern har verktyget submit\_inquiry, som låter en AI-agent skicka en offertförfrågan eller en allmän förfrågan åt en köpare.

01

Köparen frågar en AI-agent

Agenten hittar rätt produkt och förbereder förfrågan

02

Köparen bekräftar

Agenten visar exakt vad som skickas och väntar på ett tydligt ja

03

Leaden registreras i WooCommerce

En ny offertförfrågan (New Quote Request) skapas i WooCommerce, markerad som skickad via AI-agent och med samtycket dokumenterat.

- ## **01**


_SearchSearch_






Agenten hittar rätt produkt och förbereder förfrågan

- ## **02**


_SearchSearch_






Agenten visar exakt vad som skickas och väntar på ett tydligt ja

- ## **03**


_SearchSearch_






En ny offertförfrågan (New Quote Request) skapas i WooCommerce, markerad som skickad via AI-agent och med samtycket dokumenterat.


[FöregåendeFöregåendeFöregående](https://www.gomogroup.com/case/forsta-svenska-b2b-webbplatsen-med-agentiskt-lager/#prev "Föregående") [NästaNästaNästa](https://www.gomogroup.com/case/forsta-svenska-b2b-webbplatsen-med-agentiskt-lager/#next "Nästa")

Offertförfrågningar kommer in i WooCommerce precis som förfrågningar från webbplatsens offertkorg. Allmänna förfrågningar hamnar i kontaktformulärets databas med en leadreferens. Formulären för människor skyddas fortfarande av reCAPTCHA, och llms.txt och /agent-sidan hänvisar agenter till MCP-verktyget i stället.

Inbyggda skydd

AI-agenter kan utföra handlingar, men bara efter ett uttryckligt godkännande.

1

Uttryckligt samtycke

Agenten visar köparen exakt vad som skickas och väntar på ett tydligt ja. Samtycket registreras på ordern.

2

En separat ingång för AI-agenter

Formulären för människor ligger kvar bakom reCAPTCHA. llms.txt och /agent-sidan hänvisar agenter till MCP-verktyget i stället.

3

Ingen ny arbetsprocess för säljteamet

Agentleads registreras i samma vyer i WooCommerce och kontaktformulärets system som säljteamet redan använder, tydligt markerade som skickade via AI-agent.

Exempel: en agent skickar en offertförfrågan↻ Spela igen

Kan du begära en offert på den här produkten åt vårt team?

Det här skickar jag till leverantören: en offertförfrågan på 1 × konfigurerad produkt, med era företags- och kontaktuppgifter. Ska jag skicka den?

Ja, skicka den.

→ MCP-verktygsanrop: submit\_inquiry · offertförfrågan · samtycke bekräftat

✓Ny offertförfrågan (New Quote Request) skapad i WooCommerce, samtycket noterat på ordern

✓Kopplad förfrågan sparad i kontaktformulärets databas med en leadreferens

Illustrerat, anonymiserat exempel. Förenklad formulering. Ordernummer, produkt och kunduppgifter borttagna.

Resultatet

## Läsbar, citerbar och agerbar: verifierat genom tre typer av tester

Vi mäter inte bara vad som har levererats, utan också om lösningen fungerar i praktiken. Tre typer av tester ger svar på tre centrala frågor.

**Kan en AI-agent navigera på den befintliga webbplatsen?**

Googles granskning Agentic Browsing i PageSpeed Insights.

**Hittar och använder AI-plattformarna det agentiska lagret?**

Fem oberoende LLM-granskningar.

**Kan en AI-agent utföra handlingar åt en köpare?**

Ett MCP-test i en verklig användningssituation.

Mätning 1 · Google Agentic Browsing

Kategorin Agentic Browsing i Googles PageSpeed Insights poängsätter tre kontroller. Webbplatsen gick från 1 av 3 den 6 oktober 2026 till 3 av 3 den 7 oktober 2026.

Kontroll 1 · Åtgärdad

Tillgänglighetsträdet är korrekt uppbyggt

FöreUnderkänd

EfterGodkänd

Kontroll 2 · Bibehållen

Cumulative Layout Shift

FöreGodkänd

EfterGodkänd

Kontroll 3 · Åtgärdad

llms.txt följer rekommendationerna

FöreUnderkänd

EfterGodkänd

Källa: Google PageSpeed Insights, 6 och 7 oktober 2026.

Mätning 2 · Fem LLM-granskningar

Strong yes

Claude

9,5/10

ChatGPT

9,5/10

Gemini

Exceptional

Grok

Highly friendly

Perplexity

Varje plattform fick samma prompt och ingen annan kontext: ”Go to \[the client’s website\] and tell me if the website is LLM/AI/Agent friendly.” Omdömena återges med plattformarnas egna ord.

Mätning 2 · Vad de hittade

### 7 av 7 komponenter, bekräftade av alla 5 plattformar

llms.txt, /agent-sidan, ai.txt, robots.txt, OpenAPI-beskrivningen, det publika REST-API:t och MCP-servern.

Hittade komponenter7/7

Plattformar som bekräftade alla sju5/5

Mätning 3 · Skarpt MCP-test

### 2 av 2 agentleads levererades

Den 7 oktober 2026 använde en AI-agent (ChatGPT Codex) verktyget submit\_inquiry för att skicka en offertförfrågan och en allmän förfrågan, efter att användaren uttryckligen hade godkänt båda handlingarna.

Offertförfrågan

✓

Skapad i WooCommerce som en ny offertförfrågan (New Quote Request)

Allmän förfrågan

✓

Sparad i kontaktformulärets databas, kopplad till offerten

Vad det betyder

## Vad bygget visade

Projektet visade vad ett agentiskt lager kan göra för en B2B-tillverkare som säljer via offert.

→**Ingen ombyggnad krävs:** det agentiska lagret fungerar parallellt med den befintliga webbplatsen i WordPress och WooCommerce.

→**Verifierade resultat:** lösningen har utvärderats med Googles Agentic Browsing-kontroller och genom tester på fem AI-plattformar.

→**AI-agenter kan generera leads:** med köparens samtycke och ett dedikerat MCP-verktyg kan en AI-agent omvandla en fråga till en konkret offertförfrågan.

Om författaren

![Gaurav Khetan](https://www.gomogroup.com/wp-content/uploads/2026/06/gaurav-khetan-author-image.webp)

Gaurav KhetanHead of DevelopmentGaurav är Head of Development på GO MO Group.

## Är er webbplats redo för AI-agenter?

Dela er webbadress med oss, så visar vi hur AI-agenter uppfattar er webbplats i dag och vad som krävs för att göra den läsbar, citerbar och redo för AI-drivna handlingar.

[Utforska Agentic Web](https://www.gomogroup.com/vara-tjanster/agentic-web/)

### Låt oss diskutera framtiden.

Hej mitt namn är  och jag representerar , där jag arbetar som .

Du kan kontakta mig på . Jag vill komma i kontakt med er gällande .


[![Clock icon](https://www.gomogroup.com/wp-content/themes/enfold-child/images/clock-icon.svg)GothenburgLennart Torstenssonsgatan 8\\
\\
\\
412 56 Göteborg](https://share.google/4tOJokfvr4BHQ52ie)

[![Clock icon](https://www.gomogroup.com/wp-content/themes/enfold-child/images/clock-icon.svg)PuneWeWork, Fourth Floor, Raheja Woods, Kalyani Nagar, Pune, Maharashtra 411006](https://share.google/BI21uHD9GcJNLu85R)

[![Clock icon](https://www.gomogroup.com/wp-content/themes/enfold-child/images/clock-icon.svg)StockholmUpplandsgatan 7 \\
\\
111 23 Stockholm](https://maps.app.goo.gl/Htdrys9wEw1PFt2B7)

Kontakta oss, så återkommer vi med vår expertis och support!

[Kontakta oss](https://www.gomogroup.com/kontakta-oss/)

[Visit social media page](https://www.linkedin.com/company/gomo-group-ab)[Visit social media page](https://www.facebook.com/GOMOGROUP/)[Visit social media page](https://www.youtube.com/@B2BMarketingInsightsGOMO)

[![Microsoft Advertising Elite Partner 2025](https://www.gomogroup.com/wp-content/uploads/2025/01/62949-Partner-Program-Badge-Updates-CY25-LP-C1_Elite.png.webp)](https://www.gomogroup.com/case/forsta-svenska-b2b-webbplatsen-med-agentiskt-lager/#)[![Microsoft Advertising Elite Partner 2026](https://www.gomogroup.com/wp-content/uploads/2026/02/microsoft-elite-partner-2026.webp)](https://www.gomogroup.com/case/forsta-svenska-b2b-webbplatsen-med-agentiskt-lager/#)

[![Semrush Enterprise Partner](https://www.gomogroup.com/wp-content/uploads/2026/04/semrush-enterprise-partner.png)](https://agencies.semrush.com/go-mo-group/)[![Google Partner](https://www.gomogroup.com/wp-content/uploads/2026/02/google-partner-badge.svg)](https://www.google.com/partners/agency?id=4938729115)[![Ahrefs](https://www.gomogroup.com/wp-content/uploads/2026/01/ahrefs-logo.webp)](https://ahrefs.com/agencies/sweden)[![Apollo Partner](https://www.gomogroup.com/wp-content/uploads/2026/02/apollo-partner-logo.webp)](https://get.apollo.io/9nkzrhydzp2t)

![](https://www.gomogroup.com/wp-content/uploads/2023/12/go-mo-group-logo-white.png.webp)Copyright © 2026 GO MO Group

[Integritetspolicy](https://www.gomogroup.com/gdpr/) [Cookies & Regler](https://www.gomogroup.com/cookies-regler/) [AI Agent Information](https://www.gomogroup.com/agent/)

[Link to: Så lockade ett medicinteknikföretag deltagare från cirka 115 organisationer till sitt webbinariumLink to: Så lockade ett medicinteknikföretag deltagare från cirka 115 organisationer till sitt webbinarium![](https://www.gomogroup.com/wp-content/uploads/2016/01/Digital-online-marketing-80x80.jpg.webp)Så lockade ett medicinteknikföretag deltagare från cirka 115 organisationer...](https://www.gomogroup.com/case/medtech-webbinarium-b2b-eventmarknadsforing/ "Link to: Så lockade ett medicinteknikföretag deltagare från cirka 115 organisationer till sitt webbinarium")

 [Scroll to top](https://www.gomogroup.com/case/forsta-svenska-b2b-webbplatsen-med-agentiskt-lager/#top "Scroll to top")

Get AI summary of GO MO Group

[ChatGPT](https://chat.openai.com/?q=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "ChatGPT")[Claude](https://claude.ai/new?q=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "Claude")[Gemini](https://gemini.google.com/guided-learning?query=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "Gemini")[Perplexity](https://www.perplexity.ai/?q=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "Perplexity")[Grok](https://x.com/i/grok?text=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "Grok")