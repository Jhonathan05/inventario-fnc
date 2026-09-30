<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;
use App\Services\Importadores\CMDBMapperService;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop existing indexes before modifying columns
        Schema::table('funcionarios', function (Blueprint $table) {
            // Depending on the database driver, dropping constraints might need specific handling
            // but Laravel abstracts this well.
            $table->dropUnique('funcionarios_identificacion_unique');
            $table->dropIndex('idx_funcionarios_identificacion');
            $table->dropIndex('idx_funcionarios_nombres');
        });

        // 2. Change columns to TEXT to hold ciphertext, and add the hash column
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->text('identificacion')->change();
            $table->text('nombres')->change();
            $table->text('cargo')->nullable()->change();
            
            $table->string('identificacion_hash', 64)->nullable()->after('identificacion');
        });

        // 3. Encrypt existing data and generate hashes
        $funcionarios = DB::table('funcionarios')->get();
        foreach ($funcionarios as $funcionario) {
            $identificacionOriginal = $funcionario->identificacion;
            // Normalizamos igual que CMDBMapperService para consistencia
            $identificacionNormalizada = trim(preg_replace('/[\x00-\x1F\x7F\x{00A0}]+/u', ' ', (string) $identificacionOriginal));
            $identificacionNormalizada = strtoupper($identificacionNormalizada);
            $identificacionNormalizada = preg_replace('/[.\s-]+/', '', $identificacionNormalizada);

            $hash = hash_hmac('sha256', $identificacionNormalizada, config('app.key'));

            DB::table('funcionarios')
                ->where('id', $funcionario->id)
                ->update([
                    'identificacion' => Crypt::encryptString($funcionario->identificacion),
                    'identificacion_hash' => $hash,
                    'nombres' => Crypt::encryptString($funcionario->nombres),
                    'cargo' => $funcionario->cargo ? Crypt::encryptString($funcionario->cargo) : null,
                ]);
        }

        // 4. Make hash unique now that it is populated
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->unique('identificacion_hash');
        });
    }

    public function down(): void
    {
        // 1. Drop hash unique constraint
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropUnique(['identificacion_hash']);
        });

        // 2. Decrypt data
        $funcionarios = DB::table('funcionarios')->get();
        foreach ($funcionarios as $funcionario) {
            DB::table('funcionarios')
                ->where('id', $funcionario->id)
                ->update([
                    'identificacion' => substr(Crypt::decryptString($funcionario->identificacion), 0, 20),
                    'nombres' => substr(Crypt::decryptString($funcionario->nombres), 0, 100),
                    'cargo' => $funcionario->cargo ? substr(Crypt::decryptString($funcionario->cargo), 0, 100) : null,
                ]);
        }

        // 3. Revert columns and drop hash
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->dropColumn('identificacion_hash');
            
            $table->string('identificacion', 20)->change();
            $table->string('nombres', 100)->change();
            $table->string('cargo', 100)->nullable()->change();
        });

        // 4. Restore indexes
        Schema::table('funcionarios', function (Blueprint $table) {
            $table->unique('identificacion');
            $table->index('identificacion', 'idx_funcionarios_identificacion');
            $table->index('nombres', 'idx_funcionarios_nombres');
        });
    }
};
