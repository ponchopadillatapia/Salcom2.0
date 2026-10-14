<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('foreign_supplier_profiles')) {
            Schema::create('foreign_supplier_profiles', function (Blueprint $table) {
                $table->id();
                // Dueño del cuestionario: el proveedor extranjero que lo llena.
                $table->unsignedBigInteger('proveedor_id')->index();

                // ─────────────────────────────────────────────────────────
                // PDF 1 — Business Profile (C-TPAT business partner profile)
                // ─────────────────────────────────────────────────────────
                $table->string('company_name')->nullable();
                $table->string('tax_id_number')->nullable();
                $table->string('complete_address')->nullable();
                $table->string('zip_code', 20)->nullable();
                $table->string('year_established', 10)->nullable();
                $table->string('type_of_business')->nullable();
                $table->string('business_address')->nullable();
                $table->string('telephone_number', 40)->nullable();
                $table->string('number_of_employees', 20)->nullable();
                $table->string('president_owner_ceo')->nullable();
                $table->string('website')->nullable();

                // CTPAT certified? -> si sí, SVI#; si no, plan to sign up?
                $table->boolean('ctpat_certified')->nullable();
                $table->string('svi_number', 60)->nullable();
                $table->string('plan_to_sign_up', 10)->nullable();

                $table->string('professional_associations')->nullable();
                $table->boolean('owns_warehouse')->nullable();
                $table->boolean('owns_trailers')->nullable();
                $table->integer('trailers_count')->nullable();
                $table->boolean('owns_tractors')->nullable();
                $table->integer('tractors_count')->nullable();

                // Two business references
                $table->string('reference1_name')->nullable();
                $table->string('reference1_phone', 40)->nullable();
                $table->string('reference2_name')->nullable();
                $table->string('reference2_phone', 40)->nullable();

                // ─────────────────────────────────────────────────────────
                // PDF 2 — Security Confidential Questionnaire (C-TPAT)
                // Section I
                // ─────────────────────────────────────────────────────────
                $table->boolean('sec_ctpat_certified')->nullable();
                $table->string('sec_svi_number', 60)->nullable();
                $table->boolean('mutual_recognition_member')->nullable();
                $table->string('mutual_recognition_which')->nullable();

                // Section II — Yes/No security profile (nullable = sin responder)
                $table->boolean('physical_security')->nullable();
                $table->boolean('fencing')->nullable();
                $table->boolean('gates_gatehouses')->nullable();
                $table->boolean('parking_restrictions')->nullable();
                $table->boolean('locking_devices')->nullable();
                $table->boolean('lighting')->nullable();
                $table->boolean('alarm_video')->nullable();
                $table->boolean('it_security')->nullable();
                $table->boolean('access_controls')->nullable();
                $table->boolean('visitor_photo_id')->nullable();
                $table->boolean('challenge_unauthorized')->nullable();
                $table->boolean('container_security')->nullable();
                $table->boolean('container_inspection')->nullable();
                $table->boolean('container_seals')->nullable();
                $table->boolean('seal_discrepancy_procedure')->nullable();
                $table->boolean('seal_control')->nullable();
                $table->boolean('container_storage')->nullable();
                $table->boolean('procedural_security')->nullable();
                $table->boolean('documentation_processing')->nullable();
                $table->boolean('personnel_security')->nullable();
                $table->boolean('education_training')->nullable();
                $table->boolean('threat_awareness')->nullable();
                $table->boolean('documentation_processing2')->nullable();
                $table->boolean('manifest_procedures')->nullable();

                // Section III — firma de quien completó el cuestionario
                $table->string('signed_print_name')->nullable();
                $table->string('signed_title')->nullable();
                $table->string('signed_company_name')->nullable();
                $table->date('signed_date')->nullable();

                // Control de llenado: el proveedor puede guardar e ir completando.
                $table->boolean('profile_completed')->default(false);
                $table->boolean('security_completed')->default(false);

                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('foreign_supplier_profiles');
    }
};
