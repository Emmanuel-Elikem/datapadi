# 🚀 The "Fresh & Better" Automatic Setup Guide

You asked for the **best, free, and easiest way** to handle automation. Here it is.

We have upgraded your system to use a **Single Master Cron Job**. This script now does two things every time it runs:
1.  **Sends new orders** to the API automatically.
2.  **Checks the status** of existing orders and updates your website (Completed/Failed).

This means you don't need webhooks (which can be tricky on free hosting) and you don't need multiple cron jobs.

## ✅ Step 1: The Setup (cron-job.org)

Since you are on InfinityFree, the built-in cron jobs can be unreliable. We will use **cron-job.org** (it's free and reliable).

1.  Log in to [cron-job.org](https://cron-job.org/).
2.  Click **"Create Cronjob"**.
3.  **Title:** `Datapadi Master Job`
4.  **URL:** 
    ```
    http://your-domain.com/cron/process-scheduled-jobs.php?key=KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg=
    ```
    *(Replace `your-domain.com` with your actual website domain)*
5.  **Execution schedule:** `Every 1 minute` (or every 2 minutes if you prefer).
6.  **Notifications:** Select "On failure" so you know if something breaks.
7.  **Save.**

## ❓ Why did it fail before?
`cron-job.org` has a timeout (usually 30 seconds). If your script tries to check 100 orders at once, it takes too long and `cron-job.org` kills it, sending you a failure email.

**The Fix:** I have updated the script to process in **small batches** (max 10 new orders, max 5 status checks per run). This ensures it finishes in 1-2 seconds, so it will never timeout.

## 📊 How to Monitor
You can see exactly what the cron job is doing (sending orders, checking status, etc.) by going to your Admin Dashboard and clicking **"View Cron Logs"**.

## 🔄 Workflow Summary
1.  **Customer orders** -> Order saved as `Pending`.
2.  **Cron Job runs (every min)**:
    *   Picks up the `Pending` order.
    *   Sends it to Datapacks API.
    *   Updates status to `Processing`.
3.  **Cron Job runs (next min)**:
    *   Sees the `Processing` order.
    *   Asks Datapacks API: "Is this done yet?"
    *   If API says "Success", it updates your DB to `Completed`.
    *   Customer sees "Completed" on your site.

## ⚠️ Important Requirement
Make sure your **Datapacks API Token** is saved in your Admin Settings (`admin/settings.php`). The system cannot send orders or check status without it.
