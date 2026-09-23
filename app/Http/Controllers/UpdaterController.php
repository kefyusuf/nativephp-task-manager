<?php

namespace App\Http\Controllers;

use Native\Desktop\Facades\AutoUpdater;

class UpdaterController extends Controller
{
    /**
     * Ask electron-updater to check for a new release.
     * Flow: check → (UpdateAvailable → auto download) → UpdateDownloaded → install.
     */
    public function check()
    {
        AutoUpdater::checkForUpdates();

        return back();
    }

    /** Install the downloaded version and restart the app. */
    public function install()
    {
        AutoUpdater::quitAndInstall();

        return back();
    }
}
