# Recovery notes

Recovery target: project state before Antigravity edits at about 20:27 on 2026-08-04 (Asia/Saigon).

This folder was rebuilt from the local Railway/GitHub base backup:

- `D:\_recovery_backup_luanvancuoiky_wrong_railway_20260804-205124`
- Git base seen locally: `7967a7b` (`Fix try-on assets for Railway`, 2026-07-26 21:30:23 +0700)

Then it was combined with Codex work from 2026-08-04 before 20:27:

- `C:\Recovered_luanvancuoiky_before_antigravity_20260804\pre_antigravity_codex_best_effort_clean_213127`
- `C:\Recovered_luanvancuoiky_before_antigravity_20260804\pre_antigravity_from_codex_diffs_213010`
- extracted Codex patch logs in `C:\Recovered_luanvancuoiky_before_antigravity_20260804\codex_extracted_git_diffs_before_2027`

Chat-log audit source of truth:

- This recovery is based on previous Codex/chat sessions inside this exact project, `D:\luanvancuoiky`, from 2026-07-27 through 2026-08-04.
- I did not use `D:\luanvanck` as a recovery source.
- The Railway/GitHub copy was treated only as an old/base fallback for project structure and dependencies, not as the final source of truth.
- Chat-log scan found 26 Codex sessions for `D:\luanvancuoiky`.
- Chat-log manifest found 103 touched files: 89 latest updates, 11 latest adds, and 3 latest deletes.
- Current audit result: all 100 files whose latest chat-log action is add/update exist in `D:\luanvancuoiky`; the 3 files whose latest chat-log action is delete remain absent.
- Local copies of the audit files are stored in:
  - `RECOVERY_CODEX_SESSIONS_20260727_20260804.csv`
  - `RECOVERY_TOUCHED_FILES_20260727_20260804.csv`

Important recovery choices:

- Kept the new site logo reference: `public/upload/logo/logo-1.png`.
- Kept `public/css/ui-human.css` loaded by `resources/views/layouts/app.blade.php`.
- Restored `.env` from the working `D:\luanvancuoiky\.env` because the reconstructed `.env` had an empty `APP_KEY`.
- Restored newer Codex versions of cart, checkout, order detail, return request, blog, register, and try-on views.
- Restored newer Codex versions of selected controllers, model, route, and bootstrap files that passed PHP lint.
- Restored the promotion admin files from Codex chat output and fixed their recovered UTF-8 Vietnamese text.
- Did not copy `app/Http/Controllers/Admin/ReturnAdminController.php` from `pre_antigravity_from_codex_diffs_213010` because that source file has a duplicate method and fails `php -l`.
- No `.rej` patch reject files are present in this recovered folder.

Validation performed in this folder:

- `php artisan route:list --no-ansi` succeeded.
- `php -l` succeeded for 243 project PHP files, excluding `vendor`, `node_modules`, and compiled Blade cache.
- `php artisan view:cache --no-ansi` succeeded, then compiled views were cleared.
- `http://127.0.0.1:8000/` returned HTTP 200 after restoring `.env`; the HTML references `logo-1.png` and `ui-human.css`.

Note: PHP prints a local WAMP Xdebug load warning, but the Laravel/PHP commands above still succeeded.
