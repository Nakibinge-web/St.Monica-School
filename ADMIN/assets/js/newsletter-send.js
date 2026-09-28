/**
 * St. Monica Junior School CMS - Newsletter sending runner
 * Calls newsletter/send.php "batch" repeatedly until every recipient has been processed,
 * reporting progress. Used by Compose (new sends) and Sent Newsletters (resume / retry).
 */
window.NewsletterSender = (function () {
    'use strict';

    async function post(endpoint, csrf, data) {
        const body = new FormData();
        Object.entries(data).forEach(([k, v]) => body.append(k, v));
        body.append('csrf_token', csrf);
        const res = await fetch(endpoint, {
            method: 'POST',
            body,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        });
        const json = await res.json().catch(() => null);
        if (!json) throw new Error(`Server error (${res.status}).`);
        if (!json.success) throw new Error(json.message || 'Request failed.');
        return json;
    }

    /**
     * Run batches until done.
     * opts: { endpoint, csrf, campaignId, onProgress(progress), shouldStop() }
     * Resolves with the final progress object.
     */
    async function run(opts) {
        let progress = null;
        let consecutiveErrors = 0;
        while (true) {
            if (opts.shouldStop && opts.shouldStop()) return progress;
            try {
                const json = await post(opts.endpoint, opts.csrf, { action: 'batch', campaign_id: opts.campaignId });
                progress = json.data;
                consecutiveErrors = 0;
                if (opts.onProgress) opts.onProgress(progress);
                if (progress.done) return progress;
                // A whole batch failed with nothing delivered: the mail server is refusing
                // (e.g. Gmail's daily sending limit). Pause instead of marking everyone else failed;
                // the send can be resumed later from "Sent Newsletters".
                if (progress.batch_sent === 0 && progress.batch_failed >= 3) {
                    const stop = new Error(progress.last_error || 'The mail server refused the emails.');
                    stop.fatal = true;
                    throw stop;
                }
            } catch (err) {
                consecutiveErrors++;
                if (err.fatal || consecutiveErrors >= 3) {
                    err.progress = progress;
                    throw err;
                }
                await new Promise(r => setTimeout(r, 2000)); // brief pause, then retry the batch
            }
        }
    }

    return { post, run };
})();
