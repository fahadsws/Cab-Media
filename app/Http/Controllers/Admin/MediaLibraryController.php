<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Central media library (like WordPress "Uploads"). It works straight on the folders the site already uses, so every image
 * uploaded before is listed here too and nothing existing is moved, renamed or re-linked. Files are shown folder-wise.
 * Deleting a file that is still referenced by a record is refused unless the admin explicitly forces it.
 */
class MediaLibraryController extends Controller
{
    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico'];
    private const FILE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg', 'ico', 'mp4', 'webm', 'pdf'];

    /** Library roots: key => label, absolute dir, url prefix, and the string a record contains when it references a file. */
    private function roots(): array
    {
        return [
            'uploads' => ['label' => 'Uploads', 'dir' => public_path('uploads'), 'url' => asset('uploads'), 'ref' => 'uploads/'],
            'editor' => ['label' => 'Editor images', 'dir' => storage_path('app/public/editor'), 'url' => asset('storage/editor'), 'ref' => 'editor/'],
        ];
    }

    public function index()
    {
        return view('admin.media.index', ['roots' => $this->roots()]);
    }

    /** JSON: sub-folders and files of one folder (also used by the picker modal). */
    public function list(Request $r): JsonResponse
    {
        if ($r->boolean('flat')) return $this->flat(Str::lower(trim((string) $r->input('q', ''))), max(1, (int) $r->input('page', 1)));

        [$root, $rel, $dir] = $this->resolve((string) $r->input('root', 'uploads'), (string) $r->input('path', ''));
        $cfg = $this->roots()[$root];
        $q = Str::lower(trim((string) $r->input('q', '')));
        $folders = $files = [];

        if (is_dir($dir)) {
            foreach (new \DirectoryIterator($dir) as $it) {
                if ($it->isDot() || $it->getFilename()[0] === '.') continue;
                $name = $it->getFilename();
                if ($q !== '' && ! str_contains(Str::lower($name), $q)) continue;
                $relPath = ltrim($rel.'/'.$name, '/');
                $ext = Str::lower($it->getExtension());
                if ($it->isDir()) {
                    $folders[] = ['name' => $name, 'path' => $relPath, 'count' => $this->countFiles($it->getPathname())];
                } elseif (in_array($ext, self::FILE_EXT, true)) {
                    $files[] = [
                        'name' => $name, 'path' => $relPath, 'url' => $cfg['url'].'/'.$this->encode($relPath),
                        'image' => in_array($ext, self::IMAGE_EXT, true), 'ext' => $ext, 'size' => $it->getSize(), 'time' => $it->getMTime(),
                    ];
                }
            }
        }
        usort($folders, fn ($a, $b) => strcasecmp($a['name'], $b['name']));
        usort($files, fn ($a, $b) => $b['time'] <=> $a['time']);

        return response()->json(['root' => $root, 'path' => $rel, 'folders' => $folders] + $this->paginate($files, max(1, (int) $r->input('page', 1)), 48));
    }

    private function paginate(array $files, int $page, int $per): array
    {
        $pages = max(1, (int) ceil(count($files) / $per));
        $page = min($page, $pages);

        return ['files' => array_slice($files, ($page - 1) * $per, $per), 'page' => $page, 'pages' => $pages, 'total' => count($files)];
    }

    /** Picker view: every image from every folder in one list, newest first (no folders to click through). */
    private function flat(string $q, int $page): JsonResponse
    {
        $files = [];
        foreach ($this->roots() as $key => $cfg) {
            if (! is_dir($cfg['dir'])) continue;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($cfg['dir'], \FilesystemIterator::SKIP_DOTS)) as $f) {
                $ext = Str::lower($f->getExtension());
                if (! $f->isFile() || ! in_array($ext, self::IMAGE_EXT, true)) continue;
                if ($q !== '' && ! str_contains(Str::lower($f->getFilename()), $q)) continue;
                $rel = ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($cfg['dir']))), '/');
                $files[] = ['name' => $f->getFilename(), 'path' => $rel, 'folder' => ($key === 'editor' ? 'Editor images' : 'Uploads').(dirname($rel) !== '.' ? ' / '.str_replace('/', ' / ', dirname($rel)) : ''), 'url' => $cfg['url'].'/'.$this->encode($rel), 'image' => true, 'ext' => $ext, 'size' => $f->getSize(), 'time' => $f->getMTime()];
            }
        }
        usort($files, fn ($a, $b) => $b['time'] <=> $a['time']);

        return response()->json(['root' => 'uploads', 'path' => '', 'folders' => []] + $this->paginate($files, $page, 24));
    }

    public function upload(Request $r): JsonResponse
    {
        $r->validate(['files' => 'required|array|max:30', 'files.*' => 'file|max:10240|mimes:'.implode(',', self::FILE_EXT)]);
        [$root, $rel, $dir] = $this->resolve((string) $r->input('root', 'uploads'), (string) $r->input('path', ''));
        // Loose files never land in the uploads root itself: they go to library/YYYY/MM.
        if ($rel === '' && $root === 'uploads') $dir = public_path('uploads/library/'.date('Y/m'));
        File::ensureDirectoryExists($dir);

        $saved = [];
        foreach ($r->file('files') as $f) {
            $base = Str::slug(pathinfo($f->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'file';
            $ext = Str::lower($f->getClientOriginalExtension() ?: (string) $f->guessExtension());
            $name = $base.'.'.$ext;
            for ($i = 2; file_exists($dir.'/'.$name); $i++) $name = $base.'-'.$i.'.'.$ext; // never overwrite an existing file
            $f->move($dir, $name);
            $saved[] = $name;
        }

        return response()->json(['saved' => $saved]);
    }

    public function folder(Request $r): JsonResponse
    {
        $r->validate(['name' => ['required', 'string', 'max:60', 'regex:/^[A-Za-z0-9][A-Za-z0-9 _-]*$/']]);
        [, , $dir] = $this->resolve((string) $r->input('root', 'uploads'), (string) $r->input('path', ''));
        $target = $dir.'/'.trim((string) $r->input('name'));
        if (file_exists($target)) return response()->json(['message' => 'A folder or file with that name already exists.'], 422);
        File::ensureDirectoryExists($target);

        return response()->json(['ok' => true]);
    }

    /** Delete files and/or whole folders (paths[]). Anything still referenced by a record is refused (HTTP 409) until force=1. */
    public function destroy(Request $r): JsonResponse
    {
        $r->validate(['paths' => 'required|array|min:1|max:200', 'paths.*' => 'string|max:500']);
        $root = (string) $r->input('root', 'uploads');
        $targets = [];
        foreach ($r->input('paths') as $p) {
            [, $rel, $abs] = $this->resolve($root, $p);
            abort_if($rel === '', 422, 'Cannot delete the library root.');
            if (is_file($abs)) $targets[] = [$rel, $abs, false];
            elseif (is_dir($abs)) $targets[] = [$rel, $abs, true];
        }

        if (! $r->boolean('force')) {
            $inUse = [];
            foreach ($targets as [$rel, $abs, $isDir]) {
                $list = $isDir ? $this->filesIn($abs, $rel) : [$rel => $abs];
                foreach (array_keys($list) as $fileRel) {
                    if ($uses = $this->usage($root, $fileRel)) $inUse[$fileRel] = $uses;
                }
            }
            if ($inUse) {
                return response()->json(['message' => count($inUse).' file(s) are still used on the site.', 'in_use' => $inUse], 409);
            }
        }

        $deleted = 0;
        foreach ($targets as [, $abs, $isDir]) {
            if ($isDir) {
                $deleted += count($this->filesIn($abs, ''));
                File::deleteDirectory($abs);
            } elseif (@unlink($abs)) {
                $deleted++;
            }
        }

        return response()->json(['deleted' => $deleted]);
    }

    /** Validate root + relative path and return [root, cleanRel, absolutePath]. Rejects traversal and odd characters. */
    private function resolve(string $root, string $path): array
    {
        $roots = $this->roots();
        abort_unless(isset($roots[$root]), 404);
        $parts = array_values(array_filter(explode('/', str_replace('\\', '/', $path)), fn ($s) => $s !== ''));
        foreach ($parts as $s) {
            abort_unless(preg_match('/^[A-Za-z0-9][A-Za-z0-9 ._\-()]*$/', $s) && ! str_contains($s, '..'), 422, 'Invalid path.');
        }
        $rel = implode('/', $parts);
        $base = $roots[$root]['dir'];
        $abs = $base.($rel === '' ? '' : '/'.$rel);
        if (file_exists($abs)) {
            $real = realpath($abs);
            $realBase = str_replace('\\', '/', realpath($base) ?: $base);
            abort_unless($real && str_starts_with(str_replace('\\', '/', $real), $realBase), 422, 'Invalid path.');
        }

        return [$root, $rel, $abs];
    }

    private function encode(string $rel): string
    {
        return implode('/', array_map('rawurlencode', explode('/', $rel)));
    }

    private function countFiles(string $dir): int
    {
        $n = 0;
        try {
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
                if ($f->isFile()) $n++;
            }
        } catch (\Throwable) {
        }

        return $n;
    }

    /** @return array<string,string> relative path => absolute path of every file under $dir */
    private function filesIn(string $dir, string $relBase): array
    {
        $out = [];
        $dir = rtrim(str_replace('\\', '/', $dir), '/');
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
            if (! $f->isFile()) continue;
            $p = str_replace('\\', '/', $f->getPathname());
            $out[ltrim($relBase.substr($p, strlen($dir)), '/')] = $p;
        }

        return $out;
    }

    /** Where a library file is referenced: ["table.column (n)", ...]. Searches every text-like column of the database. */
    private function usage(string $root, string $rel): array
    {
        $ref = $this->roots()[$root]['ref'];
        $needles = array_unique([$ref.$rel, $ref.$this->encode($rel)]);
        $cols = DB::select(
            "SELECT TABLE_NAME t, COLUMN_NAME c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ?
             AND DATA_TYPE IN ('varchar','text','mediumtext','longtext','json')
             AND TABLE_NAME NOT IN ('migrations','sessions','cache','cache_locks','jobs','failed_jobs','automation_logs','chat_logs')",
            [DB::getDatabaseName()]
        );
        $uses = [];
        foreach ($cols as $col) {
            try {
                $q = DB::table($col->t)->where(function ($w) use ($col, $needles) {
                    foreach ($needles as $n) $w->orWhere($col->c, 'like', '%'.addcslashes($n, '%_\\').'%');
                });
                if ($n = $q->count()) $uses[] = $col->t.'.'.$col->c.' ('.$n.')';
            } catch (\Throwable) {
            }
        }

        foreach ($this->codeUsage($needles) as $file) $uses[] = 'code: '.$file;

        return $uses;
    }

    /** Files hard-coded in templates / config / assets (e.g. the default share image) are as "in use" as database references. */
    private function codeUsage(array $needles): array
    {
        $hits = [];
        foreach (['app', 'resources', 'config', 'routes', 'database/seeders', 'public/css', 'public/js'] as $d) {
            $dir = base_path($d);
            if (! is_dir($dir)) continue;
            foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $f) {
                if (! $f->isFile() || ! in_array($f->getExtension(), ['php', 'js', 'css', 'json'], true) || $f->getSize() > 2_000_000) continue;
                $txt = (string) file_get_contents($f->getPathname());
                foreach ($needles as $n) {
                    if (str_contains($txt, $n)) { $hits[] = str_replace('\\', '/', substr($f->getPathname(), strlen(base_path()) + 1)); break; }
                }
            }
        }

        return array_slice($hits, 0, 3);
    }
}
