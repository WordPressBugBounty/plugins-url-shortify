# A/B Testing with Link Rotation

**Available on: URL Shortify PRO**

---

## What is A/B Testing?

A/B testing (also called split testing) lets you send different visitors to different destination pages — automatically — through a single short link. You can then measure which page performs better and make data-driven decisions about which URL to keep.

**Example:** You have two versions of a landing page and want to know which one converts more visitors into sign-ups. Instead of guessing, you create one short link, add both URLs, and let URL Shortify split the traffic between them. After a few days or weeks, the results tell you exactly which page won.

---

## How It Works

When someone clicks your short link:

1. URL Shortify picks a destination URL from your rotation list, based on the traffic weights you set.
2. The visitor is redirected to that URL, completely seamlessly.
3. URL Shortify records which variant they saw.
4. If a Goal Link is set and the same visitor later clicks it, that visit is counted as a **conversion**.

Over time, the Link Stats page builds up a clear picture of how each variant is performing.

---

## Requirements

- URL Shortify **PRO** plan
- At least **two destination URLs** configured on a link

---

## Setting Up an A/B Test

### Step 1 — Create or Edit a Link

Go to **URL Shortify → Links** and either create a new link or click **Edit** on an existing one.

Enter your primary destination URL in the main **Target URL** field as normal.

---

### Step 2 — Enable Link Rotation

Scroll down to the **Advanced** section and find the **Dynamic Redirect** setting. Select **Link Rotation** from the dropdown.

A table will appear showing your rotation URLs:

| Target URL | Weight |
|---|---|
| *(Your primary URL — filled in automatically)* | — |
| *(Add more rows for each variant)* | e.g. 50% |

Click **Add URL** to add your second (and third, etc.) destination URL.

> **Tip:** The first row always shows your primary URL and cannot be edited here — change it in the main Target URL field at the top of the form.

---

### Step 3 — Set Traffic Weights

Each URL has a **Weight** selector that controls what percentage of visitors are sent to that URL.

**How weights work:**
- Weights do not have to add up to 100. URL Shortify calculates each URL's share automatically.
- A weight of **50** means that URL will receive roughly half the traffic relative to the others.
- Equal weights (e.g. all set to 50) means traffic is split evenly.

**Examples:**

| Variant | Weight | Traffic Share |
|---|---|---|
| Page A | 50 | 50% |
| Page B | 50 | 50% |

| Variant | Weight | Traffic Share |
|---|---|---|
| Page A | 60 | 60% |
| Page B | 30 | 30% |
| Page C | 10 | 10% |

> For a clean 50/50 A/B test, set both URLs to the same weight.

---

### Step 4 — Enable Split Test Mode *(optional but recommended)*

Toggle on the **Split Test** switch. This tells URL Shortify to track the test as an experiment with conversion goals rather than just a traffic rotator.

When Split Test mode is on:
- Results on the stats page show a **Split Test** badge.
- The winning variant is determined by **conversion rate**, not just click volume.
- The **Goal Conversion %** column appears in your results table.

---

### Step 5 — Select a Goal Link *(required for conversion tracking)*

The **Goal Link** is another short link in your account that represents a successful conversion — for example:

- A "Thank You" page link (after a form submission)
- A checkout confirmation page link
- A product page link you want visitors to reach

When a visitor clicks your A/B test link **and later clicks the Goal Link**, URL Shortify counts that as a conversion for whichever variant they saw.

**To set a Goal Link:**
1. Make sure the destination page you want to track as a conversion already has its own short link in URL Shortify.
2. Select that link from the **Goal Link** dropdown.

> **No Goal Link?** You can still use Link Rotation without a Goal Link. The results table will show click and visitor counts for each variant, but no conversion rate.

---

### Step 6 — Save

Click **Save Link**. Your short link is now live and splitting traffic between your variants.

---

## Reading Your Results

Open any link's stats page (click **Stats** next to a link in the Links list). Scroll down to the **Link Rotation Results** section.

### The Results Table

| Column | What it means |
|---|---|
| **#** | The variant letter — A is your primary URL, B is your second, C is your third, and so on. |
| **Destination URL** | The full URL this variant sends visitors to. |
| **Traffic %** | The configured weight share — how much of the traffic is directed to this variant. |
| **Total Clicks** | Every click that landed on this variant, including return visits by the same person. |
| **Unique Visitors** | The number of distinct individuals who visited this variant (based on a 1-year browser cookie). |
| **First Clicks** | Clicks from people visiting your short link for the very first time. |
| **Goal Conv. %** | *(Split Test mode only)* The percentage of unique visitors who later clicked your Goal Link. This is your conversion rate. |

The variant with the best performance is highlighted with a **▲ Leading** label and an indigo badge.

- In **Split Test mode** — the leader is the variant with the highest conversion rate.
- In **Rotation-only mode** — the leader is the variant with the most total clicks.

---

### The Summary Bar

Below the table you'll see two summary cards:

**Winning Variant card** — names the leading variant and shows its conversion rate and total conversions at a glance.

**Totals card** — shows:
- Total clicks across all variants combined
- Total conversions across all variants
- Overall conversion rate (total conversions ÷ total unique visitors)

---

## Understanding Conversion Rate

**Conversion rate = Conversions ÷ Unique Visitors × 100**

A conversion is counted when:
1. A unique visitor clicks your short link and is sent to Variant A (or B, or C).
2. That **same visitor** later clicks your Goal Link.

The conversion is attributed to whichever variant the visitor saw first.

**Example:**

| Variant | Unique Visitors | Conversions | Conversion Rate |
|---|---|---|---|
| A — Original landing page | 289 | 87 | **30.1%** ← Winner |
| B — Redesigned landing page | 184 | 37 | 20.1% |
| C — Minimal landing page | 76 | 11 | 14.5% |

In this example, Variant A is the winner with a 30.1% conversion rate, despite Variant B having a slightly higher traffic share.

---

## Tips for Better Results

**Run your test long enough.** A small number of clicks can be misleading. Aim for at least 100 unique visitors per variant before drawing conclusions.

**Test one thing at a time.** If you change multiple things between variants (headline, layout, images), you won't know which change made the difference. Keep variants as similar as possible except for the single thing you're testing.

**Set up the right Goal Link.** Make sure your Goal Link genuinely represents a meaningful action — a "Thank You" page that only appears after a form is submitted, or a checkout confirmation page. Avoid goal links that visitors might reach without completing the action you care about.

**Use equal weights for fair comparison.** When you want a clean 50/50 test, set both variants to the same weight. Unequal weights are useful when you want to send most traffic to a proven page while testing a new one cautiously (e.g. 80/20 split).

**Don't refresh the page obsessively.** Results need time to accumulate. Check back after a few days rather than hourly.

---

## Frequently Asked Questions

**Can I run a rotation with more than two URLs?**
Yes. You can add as many destination URLs as you like. Each gets its own variant letter (A, B, C, D…) and appears as a separate row in the results table.

**What happens to clicks that came in before I turned on Split Test mode?**
Clicks recorded before you enabled the Split Test toggle are still counted in Total Clicks and Unique Visitors. However, conversions are only tracked from the point the Goal Link was configured, so early clicks may show lower conversion numbers for those variants.

**Does the Goal Link have to be a link I created in URL Shortify?**
Yes. The Goal Link must be an existing short link in your URL Shortify account, because URL Shortify needs to detect when that link is clicked by the same visitor.

**Will the same visitor always see the same variant?**
No. Variant assignment happens on each click based on the configured weights. The same person visiting twice may land on different variants. For most testing scenarios this is fine, and the unique visitor count helps account for it.

**Can I pause or stop the test?**
To stop splitting traffic, edit the link and change the Dynamic Redirect setting back to the standard redirect type (or set all traffic to a single URL). Historical results remain visible on the stats page.

**Is there a minimum number of clicks needed to get reliable results?**
There is no enforced minimum, but statistically speaking, fewer than 50–100 unique visitors per variant can produce results that are difficult to trust. The more traffic you collect, the more confident you can be in the winner.

---

## Quick Setup Checklist

- [ ] On PRO plan
- [ ] Created or opened the link to test
- [ ] Set "Dynamic Redirect" to **Link Rotation**
- [ ] Added at least one extra destination URL
- [ ] Set weights for each URL
- [ ] Toggled **Split Test** on
- [ ] Selected a **Goal Link** from the dropdown
- [ ] Saved the link
- [ ] Checked back after gathering sufficient traffic
- [ ] Visited **Stats** page → scrolled to **Link Rotation Results** to review winner
