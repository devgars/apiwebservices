<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalDocument extends Model
{
    protected $table = 'fiscal_documents';

    protected $fillable = [
        'company_id',
        'subsidiary_id',
        'warehouse_id',
        'customer_id',
        'customer_address_id',
        'fiscal_document_type_id',
        'internal_number',
        'reg_date',
        'year_month',
        'serie_as_id',
        'correlative_as_number',
        'serie_fiscal_id',
        'correlative_fiscal_number',
        'seller_id',
        'auth_code_as',
        'support_as',
        'currency_id',
        'net_amount',
        'net_amount_taxed',
        'net_exonerated_amount',
        'principal_tax_amount',
        'total_amount',
        'withholding_tax_amount',
        'withholding_tax_base_amount',
        'additional_tax_code',
        'additional_tax_amount',
        'additional_tax_rate',
        'ord_order_id',
        'parent_fiscal_document_id',
        'additional_status',
        'additional_status_date',
        'down_status_reg',
        'down_status_date',
        'reg_status',
        'anullment_date',
        'user_id',
        'created_at',
        'updated_at'
    ];
}
