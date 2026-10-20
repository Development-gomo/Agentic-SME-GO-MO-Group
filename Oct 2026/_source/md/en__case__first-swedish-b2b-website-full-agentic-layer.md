URL: https://www.gomogroup.com/en/case/first-swedish-b2b-website-full-agentic-layer/
---
Agentic Web \| Reading time: 5 minutes \| October 2026

# The first Swedish B2B website with a  full agentic layer

In early October 2026, an AI agent visiting this manufacturer’s website had to make sense of pages built only for people. Today it finds a layer built for agents: plain-language files, structured product data, a public API and an MCP server. Agents can now read the catalogue, cite it correctly and send a quote request straight into the sales team’s normal workflow.

_The client is a Swedish B2B manufacturer of specialist technical equipment. The company’s name is withheld to protect client confidentiality._

3/3

Agentic Browsing checks

5/5

AI platforms: agent-friendly

7/7

agent components found

2/2

agent leads delivered

3/3

Google Agentic Browsing checks passed, up from 1/3

5/5

AI platforms rated the site agent-friendly

7/7

agent components found by every platform

2/2

agent-submitted leads delivered in a live test

**Agentic Browsing:** Google’s agent-readiness category in PageSpeed Insights.

About the client

## A niche B2B manufacturer selling through an RFQ cart

The client is a Swedish B2B manufacturer of specialist technical equipment, sold to engineering-led companies worldwide. There are no public prices: every sale starts with a request for quote.

The website runs on WordPress, with the product catalogue built on WooCommerce. Buyers configure products, add them to an RFQ cart and submit the cart as a quote request to the sales team. The human website worked well. What it lacked was any layer built for AI agents.

Opportunity

## Be the source AI agents trust, and let them act on it

Engineers increasingly start supplier research in ChatGPT, Claude, Perplexity or an agentic browser. The assistant reads a few sources and writes one answer. If it cannot read a manufacturer’s specifications cleanly, it guesses, quotes a reseller or leaves the manufacturer out.

At the start, Google’s Agentic Browsing audit passed 1 of 3 checks. The accessibility tree was not well-formed, and there was no llms.txt following the recommendations. An agent could see the pages, but it had no structured way to understand the catalogue and no safe way to send a lead.

Together with the client, we set three goals. Readable: every product, specification and company fact available to agents as clean, structured data. Citable: leading AI platforms describe the company and its products correctly. Actionable: an agent can submit a quote request or enquiry for a buyer, with the buyer’s consent, into the existing sales workflow. And no rebuild: the human website stays as it is.

Deliverables · the agentic layer, stacked

Started with an agentic readiness audit

03 · Act

Act for the buyer

Send leads, with consent

MCP serversubmit\_inquiry toolConsent capture

02 · Understand

Understand the catalogue

Exact data, not guesses

schema.org markupAPI catalogueOpenAPI descriptionPublic REST API (read-only)

01 · Read

Discover and read

Canonical facts in one place

llms.txt/agent pagerobots.txtai.txtAccessibility tree fixes

Foundation: the existing WordPress + WooCommerce website, unchanged

Strategy

## Three layers. Read, understand, act.

We built the agentic layer in three parts, each answering a question an AI agent asks when it arrives at a website.

Three layers, one agentic web

Layer 01 · Read

Discover and read · llms.txt, /agent page, robots.txt, ai.txt

Google Agentic Browsing: 3/3

Layer 02 · Understand

Understand the catalogue · schema.org, OpenAPI, REST API

Found by all five AI platforms

Layer 03 · Act

Act for the buyer · MCP server, submit\_inquiry

Live quote request delivered

All three layers sit alongside the existing WordPress site.

Layer 01 · Read

R

### Discover and read

Agents find the canonical facts in one place

Before

Agents had to parse pages built for people, and there was no llms.txt.

What we built

llms.txt, an /agent page, deliberate robots.txt and ai.txt rules, and accessibility tree fixes.

Result

Google Agentic Browsing: 3/3

Layer 02 · Understand

U

### Understand the catalogue

Exact product data, not guesses

Before

Specifications lived inside page layout.

What we built

schema.org markup, an API catalogue with an OpenAPI description, and a public read-only REST API.

Result

Found by all five AI platforms

Layer 03 · Act

A

### Act for the buyer

Quote requests and enquiries via MCP

Before

The only way to send a lead was a human form protected by reCAPTCHA.

What we built

An MCP server with a submit\_inquiry tool, connected to WooCommerce and the contact form.

Result

Live quote request delivered

All three layers sit alongside the existing WordPress site. The human website was not rebuilt.

Execution

## From reading the site to sending a quote request

Most agent-readiness work stops at reading. We went one step further. The MCP server exposes a submit\_inquiry tool that lets an AI agent send a quote request or a general enquiry on a buyer’s behalf.

01

Buyer asks an AI agent

The agent finds the right product and prepares the request

02

Buyer confirms

The agent shows exactly what will be sent and waits for a clear yes

03

Lead lands in WooCommerce

A New Quote Request, marked as agent-submitted, with consent noted

- ## **01**


_SearchSearch_






The agent finds the right product and prepares the request

- ## **02**


_SearchSearch_






The agent shows exactly what will be sent and waits for a clear yes

- ## **03**


_SearchSearch_






A New Quote Request, marked as agent-submitted, with consent noted


[PreviousPreviousPrevious](https://www.gomogroup.com/en/case/first-swedish-b2b-website-full-agentic-layer/#prev "Previous") [NextNextNext](https://www.gomogroup.com/en/case/first-swedish-b2b-website-full-agentic-layer/#next "Next")

Quote requests arrive in WooCommerce just like requests from the website’s RFQ cart. General enquiries land in the contact form database with a lead reference. The human forms stay protected by reCAPTCHA, and llms.txt and the /agent page tell agents to use the MCP tool instead.

Built-in safeguards

Agents can act. Only after a clear yes.

1

Explicit consent

The agent shows the buyer exactly what will be sent and waits for a clear yes. The consent is recorded on the order.

2

A separate door for agents

Human forms stay behind reCAPTCHA. llms.txt and the /agent page point agents to the MCP tool instead.

3

No new process for sales

Agent leads land in the same WooCommerce and form views the team already uses, marked as agent-submitted.

Example: an agent sends a quote request

Can you request a quote for this product for our team?

Here is what I will send to the supplier: a quote request for 1 × configured product, with your company and contact details. Shall I submit it?

Yes, send it.

→ MCP tool call: submit\_inquiry · quote request · consent confirmed

✓New Quote Request created in WooCommerce, consent noted on the order

✓Linked enquiry saved in the contact form database with a lead reference

Illustrative, anonymised example. Wording simplified; order number, product and customer details removed.

The result

## Readable, citable and actionable, measured three ways

We measure whether the work succeeded, not just whether it was delivered. Three independent checks answer three different questions.

**Can an agent navigate the human website?**

Google’s Agentic Browsing audit in PageSpeed Insights.

**Do AI platforms find and use the agent layer?**

Five independent LLM audits.

**Can an agent act for a buyer?**

A live MCP test.

Measure 1 · Google Agentic Browsing

Google’s Agentic Browsing category in PageSpeed Insights scores three checks. The site went from 1 of 3 on 6 October 2026 to 3 of 3 on 7 October 2026.

Check 1 · Fixed

Accessibility tree is well-formed

BeforeFailed

AfterPassed

Check 2 · Kept

Cumulative Layout Shift

BeforePassed

AfterPassed

Check 3 · Fixed

llms.txt follows recommendations

BeforeFailed

AfterPassed

Source: Google PageSpeed Insights, 6 and 7 October 2026.

Measure 2 · Five LLM audits

Strong yes

Claude

9.5/10

ChatGPT

9.5/10

Gemini

Exceptional

Grok

Highly friendly

Perplexity

Each platform got the same single prompt and no other context: “Go to \[the client’s website\] and tell me if the website is LLM/AI/Agent friendly.” Verdicts are in the platforms’ own words.

Measure 2 · What they found

### 7 of 7 components, confirmed by all 5 platforms

llms.txt, the /agent page, ai.txt, robots.txt, the OpenAPI description, the public REST API and the MCP server.

Components found7/7

Platforms confirming all seven5/5

Measure 3 · Live MCP test

### 2 of 2 agent-submitted leads delivered

On 7 October 2026, an AI agent (ChatGPT Codex) used the submit\_inquiry tool, after explicit user consent, to send a quote request and a general enquiry.

Quote request

✓

Created in WooCommerce as a New Quote Request

General enquiry

✓

Saved in the contact form database, linked to the quote

What it means

## What the build showed

The project showed what an agentic layer can do for a B2B manufacturer that sells by quote.

→**No rebuild needed:** the agentic layer sits alongside an existing WordPress and WooCommerce site.

→**Measured, not asserted:** scored by Google’s own audit and by what five AI platforms report.

→**Agents can create pipeline:** with consent and a dedicated MCP tool, an agent can turn a buyer’s question into a quote request.

About the author

![Gaurav Khetan](https://www.gomogroup.com/wp-content/uploads/2026/06/gaurav-khetan-author-image.webp)

Gaurav KhetanHead of DevelopmentGaurav is Head of Development at GO MO Group.

## Is your website ready for AI agents?

Tell us your domain and we’ll come back with what AI agents see today, and what it takes to make your site readable, citable and actionable.

[Explore Agentic Web](https://www.gomogroup.com/en/services/agentic-web/)

### Let’s discuss your future.

Hi, my name is  and I represent , where my title is . You can contact me at . I am contacting you regarding .


[![Clock icon](https://www.gomogroup.com/wp-content/themes/enfold-child/images/clock-icon.svg)GothenburgLennart Torstenssonsgatan 8\\
\\
\\
412 56 Göteborg](https://share.google/4tOJokfvr4BHQ52ie)

[![Clock icon](https://www.gomogroup.com/wp-content/themes/enfold-child/images/clock-icon.svg)PuneWeWork, Fourth Floor, Raheja Woods, Kalyani Nagar, Pune, Maharashtra 411006](https://share.google/BI21uHD9GcJNLu85R)

[![Clock icon](https://www.gomogroup.com/wp-content/themes/enfold-child/images/clock-icon.svg)London60 St Martins Lane, Covent Garden, London, WC2N 4JS](https://maps.app.goo.gl/wEFJB9m3DZbQJzM56)

Contact us, and we’ll get back to you with our expertise and support!

[Contact us](https://www.gomogroup.com/en/contact-us/)

[Visit social media page](https://www.linkedin.com/company/gomo-group-ab)[Visit social media page](https://www.facebook.com/GOMOGROUP/)[Visit social media page](https://www.youtube.com/@B2BMarketingInsightsGOMO)

[![Microsoft Advertising Elite Partner 2025](https://www.gomogroup.com/wp-content/uploads/2025/01/62949-Partner-Program-Badge-Updates-CY25-LP-C1_Elite.png.webp)](https://www.gomogroup.com/en/case/first-swedish-b2b-website-full-agentic-layer/#)[![Microsoft Advertising Elite Partner 2026](https://www.gomogroup.com/wp-content/uploads/2026/02/microsoft-elite-partner-2026.webp)](https://www.gomogroup.com/en/case/first-swedish-b2b-website-full-agentic-layer/#)

[![Semrush Enterprise Partner](https://www.gomogroup.com/wp-content/uploads/2026/04/semrush-enterprise-partner.png)](https://agencies.semrush.com/go-mo-group/)[![Google Partner](https://www.gomogroup.com/wp-content/uploads/2026/02/google-partner-badge.svg)](https://www.google.com/partners/agency?id=4938729115)[![Ahrefs](https://www.gomogroup.com/wp-content/uploads/2026/01/ahrefs-logo.webp)](https://ahrefs.com/agencies/sweden)[![Apollo Partner](https://www.gomogroup.com/wp-content/uploads/2026/02/apollo-partner-logo.webp)](https://get.apollo.io/9nkzrhydzp2t)

![](https://www.gomogroup.com/wp-content/uploads/2023/12/go-mo-group-logo-white.png.webp)Copyright © 2026 GO MO Group

[Privacy Policy](https://www.gomogroup.com/en/privacy-policy/) [Cookie Policy](https://www.gomogroup.com/en/cookie-policy/) [AI Agent Information](https://www.gomogroup.com/agent/)

[Link to: A MedTech company needed to fill the room. We did it with the right people.Link to: A MedTech company needed to fill the room. We did it with the right people.![](https://www.gomogroup.com/wp-content/uploads/2016/01/Digital-online-marketing-80x80.jpg.webp)A MedTech company needed to fill the room. We did it with the right people.](https://www.gomogroup.com/en/case/medtech-webinar-b2b-event-marketing/ "Link to: A MedTech company needed to fill the room. We did it with the right people.")

 [Scroll to top](https://www.gomogroup.com/en/case/first-swedish-b2b-website-full-agentic-layer/#top "Scroll to top")

Get AI summary of GO MO Group

[ChatGPT](https://chat.openai.com/?q=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "ChatGPT")[Claude](https://claude.ai/new?q=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "Claude")[Gemini](https://gemini.google.com/guided-learning?query=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "Gemini")[Perplexity](https://www.perplexity.ai/?q=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "Perplexity")[Grok](https://x.com/i/grok?text=As%20a%20potential%20client%2C%20help%20me%20understand%20what%20GO%20MO%20Group%20%28https%3A%2F%2Fwww.gomogroup.com%29%20does.%20Explain%20clearly%20what%20services%20they%20provide%20%28marketing%2C%20technology%2C%20AI%2C%20website%20development%2C%20etc.%29%2C%20what%20kind%20of%20companies%20they%20usually%20work%20with%2C%20what%20problems%20they%20help%20solve%2C%20what%20technologies%20or%20methods%20they%20use%2C%20and%20what%20results%20or%20business%20outcomes%20clients%20typically%20get.%20Explain%20it%20in%20simple%20language%20as%20if%20you%20were%20describing%20the%20real%20experience%20of%20working%20with%20GO%20MO%20Group%2C%20with%20concrete%20examples%20of%20services%20and%20how%20they%20help%20a%20company%20grow. "Grok")