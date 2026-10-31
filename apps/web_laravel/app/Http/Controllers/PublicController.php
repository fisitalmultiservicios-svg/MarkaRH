<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PublicController extends Controller
{
    public function contact(Request $request): RedirectResponse
    {
        $d = $request->validate(['name' => 'required|string|max:100', 'email' => 'required|email|max:200', 'company' => 'required|string|max:120', 'message' => 'required|string|min:10|max:2000', 'consent' => 'accepted']);
        DB::table('inquiries')->insert(['name' => $d['name'], 'email' => $d['email'], 'company' => $d['company'], 'message' => $d['message'], 'consented_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return redirect('/#consulta')->with('status', 'Tu consulta fue guardada. El envío de notificaciones por correo aún no está habilitado.');
    }
}
