# Cron Job & Automatic Processing Guide

## Understanding Your Order Flow

When a customer purchases a data bundle:

1. **Order Created** (3:00 PM)
   - Customer completes payment on your website
   - Order saved to database with status `pending`
   - A `scheduled_job` is created with `execute_at = 3:10 PM` (10 minutes later)
   - This 10-minute window allows for manual processing if needed

2. **Manual Processing Window** (3:00 - 3:10 PM)
   - Admin can manually process the order during this time
   - If manually processed, the job is cancelled and marked `completed`

3. **Automatic Processing Triggered** (3:10 PM)
   - Cron job runs and finds the pending job
   - The job status changes to `processing`
   - Your system calls the API to send the order to your wholesale provider
   - Order status changes to `processing`

4. **Wholesale Provider Receives Order** (3:10 PM +)
   - Your order should now appear on their dashboard
   - They process it on their end
   - Once complete, they send a webhook callback to your system

5. **Order Completed** (when provider confirms)
   - Webhook updates order status to `completed`
   - Job status becomes `completed`

---

## Why Your Order at 3 PM Shows Job at 6 PM?

**Root Cause:** Your cron job is not running, or running very infrequently.

If the job shows `execute_at = 6:00 PM` for a 3:00 PM order, this means:
- The scheduling calculation is wrong, **OR**
- You misread the time (check timezone)

**Check your timezone:**
```php
// Add this to dashboard.php or anywhere to verify
echo date_default_timezone_get();
echo date('Y-m-d H:i:s T');
```

---

## Setting Up Cron Job

### What Is a Cron Job?
A cron job is an automated task that runs on a schedule. You configure it once on your hosting, and it runs at regular intervals automatically.

### How to Configure

**Contact your hosting provider (InfinityFree) and request:**
- A cron job configured to run every **1 minute**
- Execute this URL:

```
curl "https://yourdomain.com/cron/process-scheduled-jobs.php?key=KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg="
```

Replace `yourdomain.com` with your actual domain.

### InfinityFree Cron Setup

1. Log into **InfinityFree Control Panel**
2. Go to **Advanced → Cron Job**
3. Click **Create New Cron Job**
4. Set:
   - **Execution Interval:** Every 1 minute (or every minute)
   - **Command:** The `curl` URL above
5. Click **Create**

---

## Cron Log: What It Is & How to Share It

### What Is a Cron Log?
A log file that records every time your cron job runs. It shows:
- When it ran
- How many jobs it processed
- Which orders were sent to the API
- Any errors that occurred

### Where Are My Logs?
1. Go to **Admin Dashboard → Cron Jobs & Logs**
2. You'll see all log files listed
3. Download the latest one

### How to Share With Your Provider

1. Download the cron log file (`.log` file)
2. Open it in a text editor
3. Send it to your wholesale provider with this message:

---

**Example Message to Your Provider:**

> Hi [Provider Name],
> 
> I placed an order (Order ID: ORD-20250109-ABC123) at 3:00 PM but it's not showing on your dashboard yet. 
> 
> I've attached my cron log file from today. It shows:
> - ✓ Cron job is running every minute
> - ✓ Order was scheduled for automatic processing at 3:10 PM
> - ✓ At 3:10 PM, the system sent the order to your API
> - ✓ Job marked as 'completed' after sending
>
> Can you check on your end if the order was received?

---

## Troubleshooting

### Problem: "No log files found"
**Cause:** Cron is not running  
**Fix:**
1. Contact InfinityFree support
2. Confirm cron job is active
3. Wait 5 minutes and refresh the page
4. If still no logs, cron isn't executing

### Problem: "Pending jobs keep growing"
**Cause:** Cron runs but jobs are failing  
**Fix:**
1. Check if API token is configured in Admin → Settings
2. Download the log file and look for error messages
3. Share the log with your wholesale provider or support

### Problem: "Job status is 'processing' but never 'completed'"
**Cause:** Cron crashed or API connection failed  
**Fix:**
1. Check the log file for error messages
2. Verify your API token is correct
3. Verify your internet connection is stable

### Problem: "Order shows in my database but not on provider's dashboard"
**Cause:** Order was scheduled but cron never ran, or API call failed  
**Fix:**
1. Check Admin → Diagnostics to see system health
2. Check the cron log for any error messages
3. If log shows "completed", the API received it — ask provider to check again
4. If log shows "failed", share it with your provider

---

## Admin Pages to Monitor

### **Dashboard → Cron Jobs & Logs** (`/admin/cron-logs.php`)
- View all log files
- See job status statistics
- Download logs for your provider

### **Dashboard → System Diagnostics** (`/admin/diagnostics.php`)
- Quick health check
- Identifies issues automatically
- Shows recent orders and their job statuses
- Provides recommendations

### **Dashboard → Pending Orders** (`/admin/pending-orders.php`)
- See which orders are waiting
- Manually process orders if needed
- View order details and scheduled jobs

---

## What Your Wholesale Provider Needs

When they ask for "cron job log", send them:

1. **The actual cron log file** (from Admin → Cron Jobs & Logs)
2. **The order ID** that wasn't received
3. **The timestamp** when it was placed
4. A screenshot from Admin → Diagnostics showing job status

---

## API Token Configuration

Your system can only send orders to your wholesale provider if the API token is configured.

**To set API token:**
1. Go to **Admin Dashboard → Settings**
2. Look for **Datapacks API Token**
3. Paste your token
4. Click Save

Without this, orders will be marked `pending` forever — the cron will log "API not configured".

---

## Testing Cron Manually

To test if everything works without waiting for cron:

Visit this URL in your browser (one-time test):
```
https://yourdomain.com/cron/process-scheduled-jobs.php?key=KX1CaNUddrHyVNlhYw4zI+TXiqBihB3hsPp+pRz6Xyg=
```

This will:
- Run the job processing once
- Create a log entry immediately
- You'll see the result on screen

---

## Summary

| What | Purpose | When It Runs |
|-----|---------|------------|
| **Scheduled Job** | Placeholder for when to process an order | Created when order is placed |
| **Cron** | Automated script that finds and processes jobs | Every 1 minute (you configure) |
| **API Call** | Sends order to wholesale provider | When cron finds a due job |
| **Cron Log** | Record of what cron did | After each cron execution |

---

## Quick Checklist

- [ ] Cron job configured with your hosting provider (every 1 minute)
- [ ] API token set in Admin → Settings
- [ ] Visited Admin → Cron Logs and see logs being created
- [ ] Visited Admin → Diagnostics and no critical issues show
- [ ] Recent orders show job status as "completed" (not "pending" or "processing")
