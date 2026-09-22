<?php

namespace App\Exportable;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class contacts_directory implements FromCollection, WithHeadings, ShouldAutoSize, WithTitle, WithEvents
{
    private Collection $contacts;

    public function __construct(Collection $contacts)
    {
        $this->contacts = $contacts;
    }

    public function title(): string
    {
        return 'Contactos';
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Valor',
            'Tipo',
            'Canales',
            'Cliente ID',
            'Cliente',
            'Licencia ID',
            'Licencia',
            'Etiquetas',
            'Estado',
        ];
    }

    public function collection(): Collection
    {
        return $this->contacts->map(function ($contact): array {
            $type = strtolower(trim((string) $contact->type));
            if (!in_array($type, ['email', 'phone'], true)) {
                $type = trim((string) $contact->email) !== '' ? 'email' : 'phone';
            }
            $value = trim((string) $contact->value);
            if ($value === '') {
                $value = $type === 'email' ? $contact->email : $contact->phone;
            }
            $channels = is_array($contact->channels)
                ? $contact->channels
                : (json_decode((string) $contact->channels, true) ?: []);
            $client = $contact->client;
            $license = $contact->license;
            $tags = $contact->relationLoaded('tags') ? $contact->tags : $contact->tags()->get();

            return [
                $contact->id,
                $contact->name ?: 'Contacto',
                $value,
                $type,
                implode(', ', array_values(array_unique(array_filter($channels)))),
                $contact->client_id,
                $client ? trim($client->name.' '.($client->lastname ?? '')) : null,
                $contact->license_id,
                $license?->name,
                $tags->pluck('name')->implode(', '),
                (bool) $contact->active ? 'Activo' : 'Inactivo',
            ];
        });
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A2');
                $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
                $sheet->getStyle('A1:'.$sheet->getHighestColumn().'1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                    ],
                ]);
            },
        ];
    }
}