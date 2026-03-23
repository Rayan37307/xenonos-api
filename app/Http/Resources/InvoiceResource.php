<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'invoice_id' => $this->id,
            'project_id' => $this->project_id,
            'client_id' => $this->client_id,
            'issued_by' => $this->issued_by,
            'updated_by' => $this->updated_by,
            'date_issued' => $this->date_issued,
            'due_date' => $this->due_date,
            'amount' => $this->amount,
            'status' => $this->status,
            'file_path' => $this->file_path,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
