<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Perfil + cuestionario de seguridad C-TPAT para proveedores extranjeros (importación).
 * Reemplaza, para el flujo extranjero, los datos fiscales mexicanos (RFC, Opinión SAT, etc.).
 */
class ForeignSupplierProfile extends Model
{
    protected $table = 'foreign_supplier_profiles';

    protected $fillable = [
        'proveedor_id',
        // Business Profile
        'company_name', 'tax_id_number', 'complete_address', 'zip_code',
        'year_established', 'type_of_business', 'business_address',
        'telephone_number', 'number_of_employees', 'president_owner_ceo', 'website',
        'ctpat_certified', 'svi_number', 'plan_to_sign_up',
        'professional_associations', 'owns_warehouse',
        'owns_trailers', 'trailers_count', 'owns_tractors', 'tractors_count',
        'reference1_name', 'reference1_phone', 'reference2_name', 'reference2_phone',
        // Security questionnaire — Section I
        'sec_ctpat_certified', 'sec_svi_number', 'mutual_recognition_member', 'mutual_recognition_which',
        // Section II
        'physical_security', 'fencing', 'gates_gatehouses', 'parking_restrictions',
        'locking_devices', 'lighting', 'alarm_video', 'it_security',
        'access_controls', 'visitor_photo_id', 'challenge_unauthorized',
        'container_security', 'container_inspection', 'container_seals',
        'seal_discrepancy_procedure', 'seal_control', 'container_storage',
        'procedural_security', 'documentation_processing', 'personnel_security',
        'education_training', 'threat_awareness', 'documentation_processing2',
        'manifest_procedures',
        // Section III
        'signed_print_name', 'signed_title', 'signed_company_name', 'signed_date',
        // Control
        'profile_completed', 'security_completed',
    ];

    protected $casts = [
        'ctpat_certified' => 'boolean',
        'owns_warehouse' => 'boolean',
        'owns_trailers' => 'boolean',
        'owns_tractors' => 'boolean',
        'trailers_count' => 'integer',
        'tractors_count' => 'integer',
        'sec_ctpat_certified' => 'boolean',
        'mutual_recognition_member' => 'boolean',
        'physical_security' => 'boolean',
        'fencing' => 'boolean',
        'gates_gatehouses' => 'boolean',
        'parking_restrictions' => 'boolean',
        'locking_devices' => 'boolean',
        'lighting' => 'boolean',
        'alarm_video' => 'boolean',
        'it_security' => 'boolean',
        'access_controls' => 'boolean',
        'visitor_photo_id' => 'boolean',
        'challenge_unauthorized' => 'boolean',
        'container_security' => 'boolean',
        'container_inspection' => 'boolean',
        'container_seals' => 'boolean',
        'seal_discrepancy_procedure' => 'boolean',
        'seal_control' => 'boolean',
        'container_storage' => 'boolean',
        'procedural_security' => 'boolean',
        'documentation_processing' => 'boolean',
        'personnel_security' => 'boolean',
        'education_training' => 'boolean',
        'threat_awareness' => 'boolean',
        'documentation_processing2' => 'boolean',
        'manifest_procedures' => 'boolean',
        'signed_date' => 'date',
        'profile_completed' => 'boolean',
        'security_completed' => 'boolean',
    ];

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(ProveedorUser::class, 'proveedor_id');
    }
}
