<?php

namespace App\Http\Controllers\Backend\Registro;

use App\Http\Controllers\Controller;
use App\Models\Libros;
use App\Models\NichoCobros;
use App\Models\NichoMunicipal;
use App\Models\NichoMunicipalDetalle;
use Carbon\Carbon;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RegistroController extends Controller
{
    public function __construct(){
        $this->middleware('auth');
    }


    // ***** REGISTRO PRINCIPAL ******

    public function indexRegistro()
    {
        $arrayLibros = Libros::orderBy('nombre', 'asc')->get();

        return view('backend.admin.registro.vistaregistro', compact('arrayLibros'));
    }

    public function buscarCorrelativo(Request $request)
    {
        $regla = array(
            'id' => 'required', // idlibro
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        $nicho = NichoMunicipal::where('id_libros', $request->id)
            ->orderByDesc('correlativo')
            ->first();

        $nuevoCorrelativo = $nicho ? $nicho->correlativo + 1 : 1;

        return ['success' => 1, 'correlativo' => $nuevoCorrelativo];
    }

    public function nuevoRegistro(Request $request)
    {
        $regla = array(
            'idlibro' => 'required',
            'nombreFallecido' => 'required',
            'fechaFallecido' => 'required',
            'periodoContribuyente' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        DB::beginTransaction();

        try {
            $nicho = NichoMunicipal::where('id_libros', $request->idlibro)
                ->orderByDesc('correlativo')
                ->first();

            $nuevoCorrelativo = $nicho ? $nicho->correlativo + 1 : 1;

            $regNichoMunicipal = new NichoMunicipal();
            $regNichoMunicipal->id_libros = $request->idlibro;
            $regNichoMunicipal->correlativo = $nuevoCorrelativo;
            $regNichoMunicipal->save();

            $regNichoMunicipalDetalle = new NichoMunicipalDetalle();
            $regNichoMunicipalDetalle->id_nicho_municipal = $regNichoMunicipal->id;
            $regNichoMunicipalDetalle->nombre = $request->nombreFallecido;
            $regNichoMunicipalDetalle->fecha_fallecimiento = $request->fechaFallecido;
            $regNichoMunicipalDetalle->fecha_exhumacion = $request->fechaExhumacion;
            $regNichoMunicipalDetalle->save();

            $regContribuyente = new NichoCobros();
            $regContribuyente->id_nichomunicipal_detalle = $regNichoMunicipalDetalle->id;
            $regContribuyente->nombre = $request->nombreContribuyente;
            $regContribuyente->dui = $request->duiContribuyente;
            $regContribuyente->telefono = $request->telefonoContribuyente;
            $regContribuyente->periodo = $request->periodoContribuyente;
            $regContribuyente->direccion = $request->direccionContribuyente;
            $regContribuyente->recibo = $request->reciboTesoreriaContribuyente;
            $regContribuyente->fecha_recibo = $request->fechaTesoreriaContribuyente;
            $regContribuyente->save();

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::info('ee ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }



    //******** LIBROS DETALLE **************************


    public function indexLibrosDetalle()
    {
        $arrayLibros = Libros::orderBy('nombre', 'ASC')->get();

        $primerId = optional($arrayLibros->first())->id;

        return view('backend.admin.librosdetalle.vistalibrosdetalle', compact('arrayLibros', 'primerId'));
    }


    public function tablaLibrosDetalle($id)
    {
        $listado = NichoMunicipal::where('id_libros', $id)
            ->orderBy('correlativo', 'desc')
            ->get();

        foreach ($listado as $fila) {

            $arrayNichoMuniDetalle = NichoMunicipalDetalle::where('id_nicho_municipal', $fila->id)->get();


            // COLUMNA: NOMBRE DEL FALLECIDO
            $nombresFallecidos = '';
            // COLUMNA: FECHA DE FALLECIMIENTO
            $fechasFallecimiento = '';
            // COLUMNA: FECHA EXHUMACION
            $fechasExhumacion = "";
            // COLUMNA: FECHA INICIO (desde fecha Fallecimiento o del ultimo cobro)
            $fechaInicioCiclo = "";
            // COLUMNA: FECHA VENCIMIENTO
            $proximaFechaVencimiento = "";
            // COLUMNA: PERIODO MORA PENDIENTE
            $periodosMoraVencimiento = "";
            // COLUMNA: PERIODOS PAGADOS
            $periodosPagados = "";

            // BOTON INFO PARA CADA PERSONA

            $botonNicho = "";

            // SI HAY FECHA DE EXHUMACION ME MOSTRARA EL TEXTO, SINO HARA EL CALCULO


            $hayVarios = false;
            if(count($arrayNichoMuniDetalle) > 1){
                $hayVarios = true;
            }



            foreach ($arrayNichoMuniDetalle as $item){


                $botonNicho .= '<button type="button" class="btn btn-info btn-xs"
                 onclick="vistaDetalle(' . $item->id . ')">
                 <i class="fas fa-eye" title="Info"></i>&nbsp; Info
               </button><hr><br>';


                $ff = date("d-m-Y", strtotime($item->fecha_fallecimiento));
                $fechasFallecimiento .= $ff . '<hr><br>';

                $feEx = '';
                if($item->fecha_exhumacion != null){
                    $fe = date("d-m-Y", strtotime($item->fecha_exhumacion));
                    $feEx .= $fe . '<hr><br>';
                }else{
                    $feEx .= '-' . '<hr><br>';
                }
                $fechasExhumacion .= $feEx;

                $nombresFallecidos .= $item->nombre . '<hr><br>';


                // VERIFICAR CADA CICLO DE COBROS

                // OBTENER EL ULTIMO REGISTRO
                $infoCobro = NichoCobros::where('id_nichomunicipal_detalle', $item->id)
                    ->orderByDesc('fecha_recibo') // Ordenar de más reciente a más antiguo
                    ->first();

                // SIEMPRE HABRA MINIMO 1 REGISTRO
                $conteoCobro = NichoCobros::where('id_nichomunicipal_detalle', $item->id)->count();

                if($item->fecha_exhumacion == null){
                    $periodosPagados .= $infoCobro->periodo . "<hr><br>";

                    if ($conteoCobro == 1){
                        // Solo tiene 1 registro de pago
                        $fechaInicioCiclo .= "Fecha Fallecimiento" . "<hr><br>";

                        $fechaPago = Carbon::parse($item->fecha_fallecimiento);
                        $fechaActual = Carbon::now('America/El_Salvador')->startOfDay(); // Fecha actual sin hora

                        $periodos = ($infoCobro->periodo == 1) ? 14 : 14 + (($infoCobro->periodo - 1) * 7);

                        $fechaVencimiento = $fechaPago->copy()->addYears($periodos)->startOfDay(); // Sumar los años correspondientes



                        $periodosMora = 0;
                        $estado = 'normal'; // Estado por defecto
                        $aniosTranscurridos = $fechaActual->diffInYears($fechaPago);

                        // Comprobamos si la fecha de vencimiento ya ha pasado
                        if ($aniosTranscurridos >= 14) {
                            // Primer periodo de mora al pasar 14 años
                            $periodosMora = 1;
                            // Los años adicionales que exceden los primeros 14 años
                            $aniosAdicionales = $aniosTranscurridos - 14;
                            // Cada 7 años completos adicionales suman otro periodo de mora
                            $periodosMora += floor($aniosAdicionales / 7);
                        }


                        if ($fechaActual->isSameYear($fechaVencimiento) && $fechaActual->isBefore($fechaVencimiento)) {
                            $estado = 'amarillo'; // Estamos en el año de vencimiento, pero antes de la fecha
                        } elseif ($fechaActual->isSameDay($fechaVencimiento) || $fechaActual->isAfter($fechaVencimiento)) {
                            $estado = 'rojo'; // Fecha actual es igual o posterior a la fecha de vencimiento
                        }

                        if ($estado === 'amarillo') {
                            $proximaFechaVencimiento .= '<span class="badge bg-warning">' . $fechaVencimiento->format('d-m-Y') . '</span><hr><br>';
                        } elseif ($estado === 'rojo') {
                            $proximaFechaVencimiento .= '<span class="badge bg-danger">' . $fechaVencimiento->format('d-m-Y') . '</span><hr><br>';
                        } else {
                            // No se muestra etiqueta si es "normal"
                            $proximaFechaVencimiento .= $fechaVencimiento->format('d-m-Y') . "<hr><br>";
                        }

                        $periodosMoraVencimiento .= $periodosMora . "<hr><br>";
                    } else {

                        $fechaInicioCiclo .= "Recibo (" . date("d-m-Y", strtotime($infoCobro->fecha_recibo)) . ")" . "<hr><br>";

                        $fechaPago = Carbon::parse($infoCobro->fecha_recibo); // Último pago registrado
                        // Ahora la fecha de vencimiento es la fecha del último pago más 7 años
                        // Pero se multiplica por cada periodo
                        $periodoMultiplicado = 7 * $infoCobro->periodo;

                        $fechaVencimiento = $fechaPago->copy()->addYears($periodoMultiplicado)->startOfDay();
                        $fechaActual = Carbon::now('America/El_Salvador')->startOfDay();

                        $periodosMora = 0;
                        $estado = 'normal';

                        // Calculamos los años transcurridos desde el último pago
                        $aniosTranscurridos = $fechaActual->diffInYears($fechaPago);

                        // Cada 7 años completos desde el último pago cuentan como 1 periodo de mora
                        if ($aniosTranscurridos >= 7) {
                            $periodosMora = floor($aniosTranscurridos / 7);
                        }

                        // Determinamos el estado (amarillo o rojo) según la fecha de vencimiento
                        if ($fechaActual->isSameYear($fechaVencimiento) && $fechaActual->isBefore($fechaVencimiento)) {
                            $estado = 'amarillo';
                        } elseif ($fechaActual->isSameDay($fechaVencimiento) || $fechaActual->isAfter($fechaVencimiento)) {
                            $estado = 'rojo';
                        }

                        if ($estado === 'amarillo') {
                            $proximaFechaVencimiento .= '<span class="badge bg-warning">' . $fechaVencimiento->format('d-m-Y') . '</span><hr><br>';
                        } elseif ($estado === 'rojo') {
                            $proximaFechaVencimiento .= '<span class="badge bg-danger">' . $fechaVencimiento->format('d-m-Y') . '</span><hr><br>';
                        } else {
                            $proximaFechaVencimiento .= $fechaVencimiento->format('d-m-Y') . "<hr><br>";
                        }

                        $periodosMoraVencimiento .= $periodosMora . '</span><hr><br>';
                    }
                }else{
                    $fechaInicioCiclo .= "Exhumado" . "<hr><br>";
                    $proximaFechaVencimiento .= "Exhumado" . "<hr><br>";
                    $periodosMoraVencimiento .= "Exhumado" . "<hr><br>";
                    $periodosPagados .= "Exhumado" . "<hr><br>";
                }
            }

            $fila->nombresFallecidos = $nombresFallecidos;
            $fila->fechasFallecimiento = $fechasFallecimiento;
            $fila->fechasExhumacion = $fechasExhumacion;
            $fila->fechaInicioCiclo = $fechaInicioCiclo;
            $fila->fechaProxVencimiento = $proximaFechaVencimiento;
            $fila->peridosMora = $periodosMoraVencimiento;
            $fila->periodosPagados = $periodosPagados;
            $fila->botonNicho = $botonNicho;
        }
        return view('backend.admin.librosdetalle.tablalibrosdetalle', compact('listado'));
    }


    public function infoFallecido(Request $request)
    {
        $regla = array(
            'id' => 'required', // nicho_municipal_detalle
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if($info = NichoMunicipalDetalle::where('id', $request->id)->first()){
            return ['success' => 1, 'info' => $info];
        }else{
            return ['success' => 0];
        }
    }


    public function borrarFallecido(Request $request)
    {
        $regla = array(
            'id' => 'required', // nicho_municipal_detalle
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        DB::beginTransaction();

        try {

            NichoCobros::where('id_nichomunicipal_detalle', $request->id)->delete();
            NichoMunicipalDetalle::where('id', $request->id)->delete();
            NichoMunicipal::whereNotIn('id', NichoMunicipalDetalle::pluck('id_nicho_municipal'))->delete();

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::info('ee ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }


    public function actualizarDatos(Request $request)
    {
        $regla = array(
            'id' => 'required',
            'nombreFallecido' => 'required',
            'fechaFallecido' => 'required',
        );

        // fechaExhumacion

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        NichoMunicipalDetalle::where('id', $request->id)->update([
            'nombre' => $request->nombreFallecido,
            'fecha_fallecimiento' => $request->fechaFallecido,
            'fecha_exhumacion' => $request->fechaExhumacion,
        ]);

        return ['success' => 1];
    }





    //************** COBROS ***************************


    public function indexCobros($id)
    {
        // id: nicho_municipal_detalle
        $infoNichoMuniDeta = NichoMunicipalDetalle::where('id', $id)->first();

        return view('backend.admin.librosdetalle.cobros.vistacobros', compact('id',
        'infoNichoMuniDeta'));
    }


    public function tablaCobros($id)
    {
        // id: nicho_municipal_detalle
        $listado = NichoCobros::where('id_nichomunicipal_detalle', $id)->get();

        foreach ($listado as $item) {
            $fechaReciboFormat = "";
            if($item->fecha_recibo != null){
                $fechaReciboFormat = date("d-m-Y", strtotime($item->fecha_recibo));
            }
            $item->fechaReciboFormat = $fechaReciboFormat;
        }

        return view('backend.admin.librosdetalle.cobros.tablacobros', compact('listado'));
    }

    function borrarCobro(Request $request)
    {

        $regla = array(
            'id' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        if ($info = NichoCobros::where('id', $request->id)->first()) {
            $conteo = NichoCobros::where('id_nichomunicipal_detalle', $info->id_nichomunicipal_detalle)->count();

            if($conteo == 1) {
                return ['success' => 1];
            }
        }

        NichoCobros::where('id', $request->id)->delete();
        return ['success' => 2];
    }


    public function nuevoRegistroCobro(Request $request)
    {
        $regla = array(
            'id' => 'required',
            'periodoContribuyente' => 'required',
            'fechaTesoreriaContribuyente' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        DB::beginTransaction();

        try {


            $registro = new NichoCobros();
            $registro->id_nichomunicipal_detalle = $request->id;
            $registro->nombre = $request->nombreContribuyente;
            $registro->dui = $request->duiContribuyente;
            $registro->telefono = $request->telefonoContribuyente;
            $registro->direccion = $request->direccionContribuyente;
            $registro->periodo = $request->periodoContribuyente;
            $registro->recibo = $request->reciboTesoreriaContribuyente;
            $registro->fecha_recibo = $request->fechaTesoreriaContribuyente;
            $registro->save();


            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::info('ee ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }












    public function agregarExtraFallecido(Request $request)
    {
        $regla = array(
            'id' => 'required', // nicho_municipal_detalle
            'nombreFallecido' => 'required',
            'fechaFallecido' => 'required',
            'periodoContribuyente' => 'required',
        );

        $validar = Validator::make($request->all(), $regla);

        if ($validar->fails()){ return ['success' => 0];}

        DB::beginTransaction();

        try {

            $infoData = NichoMunicipalDetalle::where('id', $request->id)->first();


            $regNichoMunicipalDetalle = new NichoMunicipalDetalle();
            $regNichoMunicipalDetalle->id_nicho_municipal = $infoData->id_nicho_municipal;
            $regNichoMunicipalDetalle->nombre = $request->nombreFallecido;
            $regNichoMunicipalDetalle->fecha_fallecimiento = $request->fechaFallecido;
            $regNichoMunicipalDetalle->fecha_exhumacion = $request->fechaExhumacion;
            $regNichoMunicipalDetalle->save();

            $regContribuyente = new NichoCobros();
            $regContribuyente->id_nichomunicipal_detalle = $regNichoMunicipalDetalle->id;
            $regContribuyente->nombre = $request->nombreContribuyente;
            $regContribuyente->dui = $request->duiContribuyente;
            $regContribuyente->telefono = $request->telefonoContribuyente;
            $regContribuyente->periodo = $request->periodoContribuyente;
            $regContribuyente->direccion = $request->direccionContribuyente;
            $regContribuyente->recibo = $request->reciboTesoreriaContribuyente;
            $regContribuyente->fecha_recibo = $request->fechaTesoreriaContribuyente;
            $regContribuyente->save();

            DB::commit();
            return ['success' => 1];

        } catch (\Throwable $e) {
            Log::info('ee ' . $e);
            DB::rollback();
            return ['success' => 99];
        }
    }





}
