<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/** Editor (TinyMCE) uploads: files live on the public disk (editor/YYYY/MM); only the URL is stored in content. The browsable library is MediaLibraryController. */
class MediaController extends Controller
{
    /** Upload one file (editor) or many (library page: files[]). */
    public function upload(Request $r)
    {
        $rule = 'file|max:10240|mimes:jpg,jpeg,png,gif,webp,avif,mp4,webm';
        if ($r->hasFile('files')) {
            $r->validate(['files' => 'required|array|max:20', 'files.*' => $rule]);
            $out = [];
            foreach ($r->file('files') as $f) {
                $path = $f->store('editor/'.date('Y/m'), 'public');
                $out[] = ['path' => $path, 'name' => basename($path), 'url' => asset('storage/'.$path)];
            }

            return response()->json(['items' => $out]);
        }
        $r->validate(['file' => 'required|'.$rule]);
        $path = $r->file('file')->store('editor/'.date('Y/m'), 'public');

        return response()->json(['location' => asset('storage/'.$path)]);
    }
}
