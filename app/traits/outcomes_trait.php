<?php 
namespace App\traits;

use Carbon\Carbon;
use Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\outcomes_import;
use Illuminate\Support\Facades\Storage;

use App\Models\outcome;

trait outcomes_trait
{
    //CREATE
    public function Outcome_CreateOutcome(
        $date
        ,$name
        ,$description
        ,$amount
        ,$type
        ,$user_id
        ,$provider_id = null
        ){
        $Response = [
            'status' => 0,
            'message' => 'Error al crear el ingreso'
        ];
        try{
            $outcome = new outcome();
            $outcome->unique_id = uniqid();
            $outcome->date = Carbon::parse($date);
            $outcome->name = $name;
            $outcome->description = $description;
            $outcome->amount = $amount;
            $outcome->type = $type;
            $outcome->user_id = $user_id;
            $outcome->provider_id = $provider_id;
            $outcome->save();
            $Response = [
                'status' => 1,
                'message' => 'Ingreso creado correctamente'
            ];
        }catch(\Exception $e){
            info('Outcome_CreateOutcome error: '.$e->getMessage());
        }
        return $Response;
    }
    //IMPORT
    public function Outcome_ImportOutcomes($file){
        $Response = [
            'status' => 0,
            'message' => 'Error al importar los datos'
        ];
        try{
            if(!$file){
                $Response['message'] = 'No se recibió ningún archivo. Verifique que haya seleccionado un archivo válido.';
                return $Response;
            }
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            $extension = strtolower($file->getClientOriginalExtension());
            if(!in_array($extension, $allowedExtensions)){
                $Response['message'] = "Formato de archivo no soportado: .{$extension}. Use archivos .xlsx, .xls o .csv";
                return $Response;
            }
            $outcomes_import = new outcomes_import();
            Excel::import($outcomes_import, $file);
            $Response = $outcomes_import->Result;
        }catch(\Maatwebsite\Excel\Validators\ValidationException $e){
            $failures = $e->failures();
            $errorMessages = [];
            foreach ($failures as $failure) {
                $errorMessages[] = "Fila {$failure->row()}: {$failure->errors()[0]}";
            }
            $Response['message'] = 'Errores de validación: ' . implode(' | ', array_slice($errorMessages, 0, 5));
            info('Outcome_ImportOutcomes validation error: '.json_encode($errorMessages));
        }catch(\PhpOffice\PhpSpreadsheet\Reader\Exception $e){
            $Response['message'] = 'Error al leer el archivo Excel: '.$e->getMessage();
            info('Outcome_ImportOutcomes reader error: '.$e->getMessage());
        }catch(\Exception $e){
            $Response['message'] = 'Error al importar: '.$e->getMessage();
            info('Outcome_ImportOutcomes error: '.$e->getMessage().' - Trace: '.$e->getTraceAsString());
        }
        return $Response;
    }
    //STATISTICS
    public function Outcome_StatisticGetOutcomeValuesByMonth($date){
        $date = Carbon::parse($date);
        $current_month = 0;
        $year_average = 0;
        $difference_porcentage = 0;
        try{
            $current_month = outcome::whereNull('deleted_at')
            ->whereMonth('date', $date->format('m'))
            ->whereYear('date', $date->format('Y'))
            ->sum('amount');
            
            // Calcular el promedio de los últimos 12 meses
            $start_date = $date->copy()->subMonths(12)->startOfMonth();
            $end_date = $date->copy()->subMonth()->endOfMonth();
            
            $last_12_months_total = outcome::whereNull('deleted_at')
                ->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->sum('amount');
            
            // Contar los meses con datos en los últimos 12 meses
            $months_with_data = outcome::whereNull('deleted_at')
                ->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')])
                ->selectRaw('COUNT(DISTINCT DATE_FORMAT(date, "%Y-%m")) as months_count')
                ->first()
                ->months_count;
            
            $year_average = $months_with_data > 0 ? $last_12_months_total / $months_with_data : 0;
            
            $difference_porcentage = ($year_average==0 || $current_month==0)?0:(round((($current_month/$year_average)-1)*100, 2));
        }catch(\Exception $e){
            info('Income_GetIncomeValuesByMonth error: '.$e->getMessage());
        }
        return [
            'status' => 1,
            'data' => [
                'month' => $date->format('m'),
                'current_month' => number_format($current_month, 0,',','.'),
                'year_average' => number_format($year_average, 0,',','.'),
                'difference_porcentage' => $difference_porcentage
            ]
        ];
    }
    public function Outcome_StatisticGetOutcomesByMonthRange($start_date, $end_date){
        $Response = [
            'status' => 1,
            'message' => '',
            'data' => [
                'outcomes_by_month' => [],
                'outcomes_count' => 0,
                'outcomes_average' => 0,
                'outcomes_max' => 0,
                'outcomes_min' => 0,
                'outcomes_total' => 0,
                'month_labels' => []
            ]
        ];
        try{
            $start_date = Carbon::parse($start_date)->startOfMonth();
            $end_date = Carbon::parse($end_date)->endOfMonth();
            $outcomes = outcome::whereNull('deleted_at')
            ->whereBetween('date', [$start_date->format('Y-m-d'), $end_date->format('Y-m-d')]);
            $Response['data']['outcomes_count'] = $outcomes->count();
            $Response['data']['outcomes_total'] = round($outcomes->sum('amount'));
            $Response['data']['outcomes_total_string'] = number_format($Response['data']['outcomes_total'], 0,',','.');
            $outcomes = $outcomes->get();
            //get months range name
            $current_date = $start_date;
            while($current_date <= $end_date){
                $Response['data']['month_labels'][] = strtoupper($current_date->format('M'));
                $start_date_string = $current_date->format('Y-m-d H:i:s');
                $end_date_string = $current_date->copy()->endOfMonth()->format('Y-m-d H:i:s');
                $Response['data']['outcomes_by_month'][] = round($outcomes->where('date', '>=', $start_date_string)->where('date', '<=', $end_date_string)->sum('amount'));
                $current_date = $current_date->addMonth();
            }
            $Response['data']['outcomes'] = $outcomes;
            $Response['data']['outcomes_by_month'] = collect( $Response['data']['outcomes_by_month']);
            $Response['data']['outcomes_max'] = round($Response['data']['outcomes_by_month']->max());
            $Response['data']['outcomes_min'] = round($Response['data']['outcomes_by_month']->min());
            $Response['data']['outcomes_average'] = round($Response['data']['outcomes_by_month']->avg());
            $Response['data']['outcomes_average_string'] = number_format($Response['data']['outcomes_average'], 0,',','.');
        }catch(\Exception $e){
            info('Outcome_StatisticGetOutcomesByMonthRange error: '.$e->getMessage());
        }
        return $Response;
    }
    public function Outcome_GetOutcomesByDateRangeReport(
        $date_from
        ,$date_to
    ){
        $Reponse = [
            'status' => 0,
            'message' => 'No se encontraron usuarios',
            'data' => []
        ];
        try{
            $date_from = Carbon::parse($date_from);
            $date_to = Carbon::parse($date_to);
            $Reponse = $this->Outcome_GetOutcomesByDateRange($date_from, $date_to);
            if($Reponse['status'] == 0){
                return $Reponse;
            }
            $outcomes = $Reponse['data'];
            $date_diff = $date_to->diffInDays($date_from);
            if($date_diff < 31){
                $report = $outcomes->groupBy(function($date) {
                    return Carbon::parse($date->date)->format('d M Y');
                })->map(function($grupped_outcomes) {
                    // Return the count of outcomes per day
                    return [
                        'label' => $grupped_outcomes->first()->date->format('d M Y'). ' - '.$grupped_outcomes->count(),
                        'total' => $grupped_outcomes->count()
                    ];
                });
                $all_days = collect();
                $current_day = $date_from->copy();
                while ($current_day->lessThanOrEqualTo($date_to)) {
                    $all_days->put($current_day->format('Y-m-d'), [
                        'label' => $current_day->format('d M Y'),
                        'total' => 0
                    ]);
                    $current_day->addDay();
                }
                $report = $all_days->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }else if($date_diff<365){
                //sum outcome by month
                $report = $outcomes->groupBy(function($date) {
                    return Carbon::parse($date->date)->format('M Y');
                })->map(function($grupped_outcomes) {
                    // Return the count of outcomes per month
                    return [
                        'label' => Carbon::parse($grupped_outcomes->first()->date)->format('M Y'). ' - '.$grupped_outcomes->sum('amount'),
                        'total' => $grupped_outcomes->sum('amount')
                    ];
                });
                // Generate an array of all months within the range
                $all_months = collect();
                $current_month = $date_from->copy();
                while ($current_month->lessThanOrEqualTo($date_to)) {
                    $all_months->put($current_month->format('Y-m'), [
                        'label' => $current_month->format('M Y'),
                        'total' => 0
                    ]);
                    $current_month->addMonth();
                }
                $report = $all_months->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }else{
                //sum outcome by year
                $report = $outcomes->groupBy(function($date) {
                    return Carbon::parse($date->date)->format('Y');
                })->map(function($grupped_outcomes) {
                    // Return the count of outcomes per year
                    return [
                        'label' =>  Carbon::parse($grupped_outcomes->first()->date)->format('Y'). ' - '.$grupped_outcomes->sum('amount'),
                        'total' => $grupped_outcomes->sum('amount')
                    ];
                });
                // Generate an array of all years within the range
                $all_years = collect();
                $current_year = $date_from->copy();
                while ($current_year->lessThanOrEqualTo($date_to)) {
                    $all_years->put($current_year->format('Y'), [
                        'label' => $current_year->format('Y'),
                        'total' => 0
                    ]);
                    $current_year->addYear();
                }
                $report = $all_years->map(function($year) use ($report) {
                    // If the year exists in the report, update the total
                    if ($report->has($year['label'])) {
                        $year['total'] = $report->get($year['label'])['total'];
                    }
                    return $year;
                });
            }
            Storage::disk('reports')->put('outcomes'.Session::get('user')['unique_id'].'.json', json_encode($outcomes));
            $Reponse = [
                'status' => 1,
                'message' => 'Reporte de gastos obtenido',
                'data' => [
                    'outcomes' => $outcomes,
                    'report' => $report
                ]
            ];
        }catch(\Exception $e){
            info('Outcome_GetOutcomesByDateRangeReport error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
        return $Reponse;
    }
    public function Outcome_GetOutcomesByDateRange(
        $date_from
        ,$date_to
    ){
        try{
            $outcomes = outcome::
            whereDate('date', '>=', $date_from)
            ->whereDate('date', '<=', $date_to)
            ->orderBy('date', 'asc')
            ->get();
            return [
                'status' => 1,
                'message' => 'Gastos obtenidos',
                'data' => $outcomes
            ];
        }catch(\Exception $e){
            info('Outcome_GetOutcomesByDateRange error: '.$e->getMessage());
            return [
                'status' => 0,
                'message' => $e->getMessage()
            ];
        }
    }

    public function Outcome_GetOutcomes($request)
    {
        $page   = (int) $request->input('page', 1);
        $size   = (int) $request->input('size', 10);
        $from   = $request->input('from');
        $to     = $request->input('to');
        $search = $request->input('search');

        $Response = [
            'status'     => 0,
            'message'    => 'Error al obtener outcomes',
            'data'       => null,
            'pagination' => null,
        ];

        try {
            $query = outcome::query()->withTrashed();

            // filtro de fechas
            if ($from) {
                $query->where('date', '>=', Carbon::parse($from)->startOfDay());
            }
            if ($to) {
                $query->where('date', '<=', Carbon::parse($to)->endOfDay());
            }

            // filtro de búsqueda en name o description
            if ($search) {
                $query->where(function($q) use($search) {
                    $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('unique_id', 'like', "%{$search}%")
                    ->orWhere('amount', 'like', "%{$search}%");
                });
            }

            // conteo y paginado
            $total = $query->count();
            $totalPages = (int) ceil($total / $size);

            $data = $query
                ->select(['id','unique_id','date','type','name','description','amount', 'deleted_at'])
                ->orderBy('date','desc')
                ->skip($size * ($page - 1))
                ->take($size)
                ->get();

            $Response['status']     = 1;
            $Response['message']    = 'Outcomes obtenidos';
            $Response['data']       = $data;
            $Response['pagination'] = [
                'page'       => $page,
                'size'       => $size,
                'total'      => $total,
                'totalPages' => $totalPages,
            ];
        } catch (\Exception $e) {
            $Response['message'] = 'Excepción: '.$e->getMessage();
        }

        return $Response;
    }

    public function Outcome_DeleteOutcome(int $id)
    {
        $Response = [
            'status'  => 0,
            'message' => 'Error al eliminar outcome',
            'data'    => null,
        ];

        try {
            $outcome = outcome::where('id', $id)
                ->whereNull('deleted_at')
                ->first();

            if (! $outcome) {
                $Response['message'] = 'Outcome no encontrado o ya eliminado';
                return $Response;
            }

            // Soft-delete
            $outcome->deleted_at = Carbon::now();
            $outcome->save();

            $Response['status']  = 1;
            $Response['message'] = 'Outcome eliminado correctamente';
        } catch (\Exception $e) {
            $Response['message'] = 'Excepción: '.$e->getMessage();
        }

        return $Response;
    }

    public function Outcome_RecoverOutcome(int $id)
    {
        $Response = [
            'status'  => 0,
            'message' => 'Error al recuperar outcome',
            'data'    => null,
        ];

        try {
            $outcome = Outcome::withTrashed()
            ->where('id', $id)
            ->first();

            $outcome->deleted_at = null;
            $outcome->save();

            $Response['status']  = 1;
            $Response['message'] = 'Outcome recuperado correctamente';
        } catch (\Exception $e) {
            $Response['message'] = 'Excepción: '.$e->getMessage();
        }

        return $Response;
    }
}