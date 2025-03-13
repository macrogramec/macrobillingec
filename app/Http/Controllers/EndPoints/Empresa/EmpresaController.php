<?php

namespace App\Http\Controllers\EndPoints\Empresa;

use App\Http\Controllers\Controller;
use App\Http\Requests\EndPoints\Empresa\CreateEmpresaRequest;
use App\Http\Requests\EndPoints\Empresa\UpdateEmpresaRequest;
use App\Http\Resources\EndPoints\Empresa\EmpresaResource;
use App\Http\Resources\EndPoints\Empresa\EmpresaCollection;
use App\Models\Empresa;
use App\Services\EndPoints\Empresa\EmpresaService;
use App\Services\SoapSriService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @OA\Tag(
 *     name="Empresa",
 *     description="Endpoints para gestión de empresas"
 * )
 */
class EmpresaController extends Controller
{
    protected $empresaService;

    public function __construct(EmpresaService $empresaService)
    {
        $this->empresaService = $empresaService;
    }

    /**
     * @OA\Get(
     *     path="/empresas",
     *     summary="Obtener listado de empresas",
     *     description="Retorna un listado paginado de empresas con filtros opcionales",
     *     operationId="getEmpresas",
     *     tags={"Administracion"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="ruc",
     *         in="query",
     *         description="Filtrar por RUC",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="razon_social",
     *         in="query",
     *         description="Filtrar por razón social",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="ambiente",
     *         in="query",
     *         description="Filtrar por ambiente",
     *         required=false,
     *         @OA\Schema(type="string", enum={"produccion", "pruebas"})
     *     ),
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="Elementos por página",
     *         required=false,
     *         @OA\Schema(type="integer", default=10)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Listado de empresas obtenido exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="ruc", type="string", example="0992877878001"),
     *                     @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
     *                     @OA\Property(property="nombre_comercial", type="string", example="EMPRESA EJEMPLO"),
     *                     @OA\Property(property="direccion_matriz", type="string", example="Guayaquil - Ecuador"),
     *                     @OA\Property(property="obligado_contabilidad", type="boolean", example=true),
     *                     @OA\Property(property="contribuyente_especial", type="string", example="12345"),
     *                     @OA\Property(property="ambiente", type="string", example="produccion"),
     *                     @OA\Property(property="tipo_emision", type="string", example="normal"),
     *                     @OA\Property(property="correos_notificacion", type="array", @OA\Items(type="string")),
     *                     @OA\Property(property="regimen_microempresas", type="boolean", example=false),
     *                     @OA\Property(property="agente_retencion", type="string", example="1"),
     *                     @OA\Property(property="rimpe", type="boolean", example=false),
     *                     @OA\Property(property="created_at", type="string", format="datetime"),
     *                     @OA\Property(property="updated_at", type="string", format="datetime")
     *                 )
     *             ),
     *             @OA\Property(property="links", type="object"),
     *             @OA\Property(property="meta", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado"
     *     )
     * )
     */
    public function index(Request $request)
    {
        $empresas = $this->empresaService->getAll(
            $request->only(['ruc', 'razon_social', 'ambiente']),
            $request->input('per_page', 10)
        );
        return new EmpresaCollection($empresas);
    }


    /**
     * @OA\Post(
     *     path="/empresas",
     *     summary="Crear una nueva empresa",
     *     tags={"Empresa"},
     *     security={{"oauth2": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"ruc","razon_social","direccion_matriz","obligado_contabilidad","ambiente","tipo_emision"},
     *             @OA\Property(property="ruc", type="string", example="0992877878001"),
     *             @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
     *             @OA\Property(property="nombre_comercial", type="string", example="EMPRESA EJEMPLO"),
     *             @OA\Property(property="direccion_matriz", type="string", example="Guayaquil - Ecuador"),
     *             @OA\Property(property="obligado_contabilidad", type="boolean", example=true),
     *             @OA\Property(property="contribuyente_especial", type="string", example="12345"),
     *             @OA\Property(property="ambiente", type="string", enum={"produccion", "pruebas"}, example="produccion"),
     *             @OA\Property(property="tipo_emision", type="string", enum={"normal", "contingencia"}, example="normal"),
     *             @OA\Property(property="regimen_microempresas", type="boolean", example=false),
     *             @OA\Property(property="agente_retencion", type="string", example="1"),
     *             @OA\Property(property="rimpe", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Empresa creada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="ruc", type="string", example="0992877878001"),
     *                 @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
     *                 @OA\Property(property="nombre_comercial", type="string", example="EMPRESA EJEMPLO"),
     *                 @OA\Property(property="direccion_matriz", type="string", example="Guayaquil - Ecuador"),
     *                 @OA\Property(property="obligado_contabilidad", type="boolean", example=true),
     *                 @OA\Property(property="contribuyente_especial", type="string", example="12345"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(property="tipo_emision", type="string", example="normal"),
     *                 @OA\Property(property="regimen_microempresas", type="boolean", example=false),
     *                 @OA\Property(property="agente_retencion", type="string", example="1"),
     *                 @OA\Property(property="rimpe", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Los datos proporcionados no son válidos"),
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(CreateEmpresaRequest $request)
    {
        $empresa = $this->empresaService->create($request->validated());
        return new EmpresaResource($empresa);
    }

    /**
     * @OA\Get(
     *     path="/empresas/{id}",
     *     summary="Obtener una empresa específica",
     *     tags={"Empresa"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empresa encontrada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="ruc", type="string", example="0992877878001"),
     *                 @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
     *                 @OA\Property(property="nombre_comercial", type="string", example="EMPRESA EJEMPLO"),
     *                 @OA\Property(property="direccion_matriz", type="string", example="Guayaquil - Ecuador"),
     *                 @OA\Property(property="obligado_contabilidad", type="boolean", example=true),
     *                 @OA\Property(property="contribuyente_especial", type="string", example="12345"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(property="tipo_emision", type="string", example="normal"),
     *                 @OA\Property(property="regimen_microempresas", type="boolean", example=false),
     *                 @OA\Property(property="agente_retencion", type="string", example="1"),
     *                 @OA\Property(property="rimpe", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empresa no encontrada",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Empresa no encontrada")
     *         )
     *     )
     * )
     */
    public function show(int $id)
    {
        $empresa = $this->empresaService->findById($id);
        return new EmpresaResource($empresa);
    }

    /**
     * @OA\Put(
     *     path="/empresas/{id}",
     *     summary="Actualizar una empresa existente",
     *     tags={"Empresa"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
     *             @OA\Property(property="nombre_comercial", type="string", example="EMPRESA EJEMPLO"),
     *             @OA\Property(property="direccion_matriz", type="string", example="Guayaquil - Ecuador"),
     *             @OA\Property(property="obligado_contabilidad", type="boolean", example=true),
     *             @OA\Property(property="contribuyente_especial", type="string", example="12345"),
     *             @OA\Property(property="ambiente", type="string", enum={"produccion", "pruebas"}, example="produccion"),
     *             @OA\Property(property="tipo_emision", type="string", enum={"normal", "contingencia"}, example="normal"),
     *             @OA\Property(property="regimen_microempresas", type="boolean", example=false),
     *             @OA\Property(property="agente_retencion", type="string", example="1"),
     *             @OA\Property(property="rimpe", type="boolean", example=false)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empresa actualizada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="ruc", type="string", example="0992877878001"),
     *                 @OA\Property(property="razon_social", type="string", example="EMPRESA EJEMPLO S.A."),
     *                 @OA\Property(property="nombre_comercial", type="string", example="EMPRESA EJEMPLO"),
     *                 @OA\Property(property="direccion_matriz", type="string", example="Guayaquil - Ecuador"),
     *                 @OA\Property(property="obligado_contabilidad", type="boolean", example=true),
     *                 @OA\Property(property="contribuyente_especial", type="string", example="12345"),
     *                 @OA\Property(property="ambiente", type="string", example="produccion"),
     *                 @OA\Property(property="tipo_emision", type="string", example="normal"),
     *                 @OA\Property(property="regimen_microempresas", type="boolean", example=false),
     *                 @OA\Property(property="agente_retencion", type="string", example="1"),
     *                 @OA\Property(property="rimpe", type="boolean", example=false)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empresa no encontrada"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación"
     *     )
     * )
     */
    public function update(UpdateEmpresaRequest $request, Empresa $empresa)
    {
        $empresa = $this->empresaService->update($empresa, $request->validated());
        return new EmpresaResource($empresa);
    }

    /**
     * @OA\Delete(
     *     path="/empresas/{id}",
     *     summary="Eliminar una empresa",
     *     description="Elimina una empresa si no tiene establecimientos asociados",
     *     tags={"Empresa"},
     *     security={{"oauth2": {}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID de la empresa a eliminar",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Empresa eliminada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Empresa eliminada exitosamente"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empresa no encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Empresa no encontrada"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="No se puede eliminar la empresa",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No se puede eliminar la empresa porque tiene establecimientos asociados"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No autorizado"
     *             )
     *         )
     *     )
     * )
     */
    public function destroy(Empresa $empresa)
    {
        $this->empresaService->delete($empresa);
        return response()->json(['message' => 'Empresa eliminada exitosamente']);
    }
    /**
     * @OA\Post(
     *     path="/empresas/actualizarFirma",
     *     summary="Actualizar firma electrónica",
     *     description="Actualiza la firma electrónica de una empresa utilizando un string en formato base64",
     *     tags={"Empresa"},
     *     security={{"oauth2": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"password", "firma", "empresa_id", "opcion"},
     *             @OA\Property(
     *                 property="password",
     *                 type="string",
     *                 minLength=6,
     *                 example="password123",
     *                 description="Contraseña de la firma electrónica"
     *             ),
     *             @OA\Property(
     *                 property="firma",
     *                 type="string",
     *                 format="text",
     *                 description="Contenido de la firma electrónica en formato base64",
     *                 example="MIIJrgIBAzCCCWoGCSqGSIb3DQEHAaCCCVsEgglXMIIJUzCCBecGCSqGSIb..."
     *             ),
     *             @OA\Property(
     *                 property="empresa_id",
     *                 type="string",
     *                 format="uuid",
     *                 example="123e4567-e89b-12d3-a456-426614174000",
     *                 description="UUID de la empresa a la que se asociará la firma"
     *             ),
     *             @OA\Property(
     *                 property="opcion",
     *                 type="integer",
     *                 enum={0, 1},
     *                 example=1,
     *                 description="Opción de procesamiento (0: verificar, 1: actualizar)"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Firma electrónica actualizada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=true
     *             ),
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Firma electrónica actualizada exitosamente"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Error en formato de datos",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="La firma no está en formato base64 válido"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Los datos proporcionados no son válidos"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empresa no encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Empresa no encontrada"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No autorizado"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Error del servidor",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="success",
     *                 type="boolean",
     *                 example=false
     *             ),
     *             @OA\Property(
     *                 property="error",
     *                 type="string",
     *                 example="Error al conectar con el servicio SOAP"
     *             )
     *         )
     *     )
     * )
     */
    public function actualizarFirmaElectronica(Request $request)
    {
        // 1. Validación de la request
        $validated = $request->validate([
            'password' => 'required|string|min:6',
            'firma' => [
                'required',
                'string',
            ],
            'empresa_id' => [
                'required',
                'string',
                'uuid',
                'exists:empresas,uuid'
            ],
            'opcion' => 'required|integer|in:0,1'
        ], [
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'firma.required' => 'La firma electrónica es requerida',
            'firma.string' => 'La firma debe ser una cadena válida en base64',
            'empresa_id.required' => 'El ID de empresa es requerido',
            'empresa_id.uuid' => 'El ID de empresa debe ser un UUID válido',
            'empresa_id.exists' => 'La empresa no existe',
            'opcion.required' => 'La opción es requerida',
            'opcion.in' => 'La opción debe ser 0 o 1'
        ]);
        $decodedData = base64_decode($validated['firma'], true);
        if ($decodedData === false) {
            return response()->json([
                'success' => false,
                'error' => 'La firma no está en formato base64 válido'
            ], 400);
        }
        $wsd = "http://172.30.2.28:9081/SERV_VALIDADOR_FIRMA_ELECT.svc?singleWsdl";
        $wdsURL = "http://172.30.2.28:9081/SERV_VALIDADOR_FIRMA_ELECT.svc";

        $soapService = new SoapSriService($wsd,$wdsURL);
        if (!$soapService->isConnected()) {
            return response()->json([
                'success' => false,
                'error' => $soapService
            ], 500);
        }
        $firma = [
            'password' => $validated['password'],
            'firma' => $validated['firma'],
            'empresa_id' => $validated['empresa_id'],
            'opcion' => 0
        ];
        \Log::error('Trama >Enviada: ', [
            'trace' => $firma
        ]);
        $tiempoEnvioSRI = microtime(true);
        $resultadoSoap = $soapService->enviarFirma($firma);
        $tiempoPostAPI = microtime(true);
        return response()->json($resultadoSoap, 201);
       // dd($resultadoSoap);

    }


    /**
     * @OA\Post(
     *     path="/empresas/actualizarFirmaArchivo",
     *     summary="Actualizar firma electrónica",
     *     description="Actualiza la firma electrónica de una empresa",
     *     tags={"Empresa"},
     *     security={{"oauth2": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"password", "firma", "empresa_id", "opcion"},
     *                 @OA\Property(
     *                     property="password",
     *                     type="string",
     *                     minLength=6,
     *                     example="password123"
     *                 ),
     *                 @OA\Property(
     *                     property="firma",
     *                     description="Archivo de firma electrónica (.p12 o .pfx)",
     *                     type="string",
     *                     format="binary"
     *                 ),
     *                 @OA\Property(
     *                     property="empresa_id",
     *                     type="string",
     *                     format="uuid",
     *                     example="123e4567-e89b-12d3-a456-426614174000"
     *                 ),
     *                 @OA\Property(
     *                     property="opcion",
     *                     type="integer",
     *                     enum={0, 1},
     *                     example=1
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Firma electrónica actualizada exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Firma electrónica actualizada exitosamente"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Los datos proporcionados no son válidos"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empresa no encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Empresa no encontrada"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No autorizado"
     *             )
     *         )
     *     )
     * )
     */
    public function actualizarFirmaArchivo(Request $request)
    {
        // 1. Validación de la request
        $validated = $request->validate([
            'password' => 'required|string|min:6',
            'firma' => [
                'required',
                'file',
                function ($attribute, $value, $fail) {
                    // Obtener extensión y MIME type
                    $extension = strtolower($value->getClientOriginalExtension());
                    $mimeType = $value->getMimeType();

                    // Verificar si es un archivo p12/pfx válido por extensión o MIME type
                    $validExtensions = ['p12', 'pfx'];
                    $validMimeTypes = ['application/x-pkcs12', 'application/pkcs12', 'application/x-pkcs8'];

                    if (!in_array($extension, $validExtensions) && !in_array($mimeType, $validMimeTypes)) {
                        $fail('La firma debe ser un archivo de tipo .p12 o .pfx');
                    }

                    // También verificamos el tamaño (max 2MB)
                    if ($value->getSize() > 2 * 1024 * 1024) {
                        $fail('El archivo de firma no debe exceder 2MB');
                    }
                },
                'max:2048', // Máximo 2MB
            ],
            'empresa_id' => [
                'required',
                'string',
                'uuid',
                'exists:empresas,uuid'
            ],
            'opcion' => 'required|integer|in:0,1'
        ], [
            'password.required' => 'La contraseña es requerida',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres',
            'firma.required' => 'La firma electrónica es requerida',
            'firma.file' => 'La firma debe ser un archivo',
            'firma.max' => 'La firma no debe exceder 2MB',
            'empresa_id.required' => 'El ID de empresa es requerido',
            'empresa_id.uuid' => 'El ID de empresa debe ser un UUID válido',
            'empresa_id.exists' => 'La empresa no existe',
            'opcion.required' => 'La opción es requerida',
            'opcion.in' => 'La opción debe ser 0 o 1'
        ]);

        try {
            // Buscar la empresa
            $empresa = Empresa::where('uuid', $validated['empresa_id'])->firstOrFail();

            // 2. Procesar el archivo de firma
            $firmaFile = $request->file('firma');

            // Log para depuración - ayuda a identificar qué tipo de archivo se está recibiendo
            \Log::info('Procesando archivo de firma', [
                'nombre_archivo' => $firmaFile->getClientOriginalName(),
                'extension' => $firmaFile->getClientOriginalExtension(),
                'tamaño' => $firmaFile->getSize(),
                'tipo_mime' => $firmaFile->getMimeType(),
                'empresa_ruc' => $empresa->ruc,
                'empresa_uuid' => $empresa->uuid
            ]);

            // Obtener el contenido binario del archivo
            $firmaContent = file_get_contents($firmaFile->getRealPath());
            if ($firmaContent === false) {
                throw new \Exception('No se pudo leer el contenido del archivo de firma');
            }

            // Convertir a base64 para enviar al servicio SOAP
            $firmaBase64 = base64_encode($firmaContent);

            // Verificar si el directorio existe, si no, crearlo
            $dirPath = "firmasElectronicas/{$empresa->uuid}";

            // Comprobar si el directorio tiene permisos de escritura
            if (!Storage::disk('public')->exists($dirPath)) {
                Storage::disk('public')->makeDirectory($dirPath, 0755, true);
            }
            $filePath = "{$dirPath}/{$empresa->ruc}.p12";
            $saved = Storage::disk('public')->put($filePath, $firmaContent);

            // Ruta completa del archivo
            $filePath = "{$dirPath}/{$empresa->ruc}.p12";

            // Guardar el contenido del archivo (no el objeto File)
            $saved = Storage::put($filePath, $firmaContent);
            if (!$saved) {
                throw new \Exception("No se pudo guardar el archivo en {$filePath}");
            }

            \Log::info("Archivo guardado correctamente", [
                'path' => $filePath,
                'size' => strlen($firmaContent)
            ]);

            // Verificar que el archivo se haya guardado correctamente
            if (!Storage::exists($filePath)) {
                throw new \Exception("El archivo no existe después de guardarlo: {$filePath}");
            }

            // Preparar datos para el servicio SOAP
            $firma = [
                'password' => $validated['password'],
                'firma' => $firmaBase64,
                'empresa_id' => $validated['empresa_id'],
                'opcion' => $validated['opcion']
            ];

            // Configuración del servicio SOAP
            $wsd = "http://172.30.2.28:9081/SERV_VALIDADOR_FIRMA_ELECT.svc?singleWsdl";
            $wdsURL = "http://172.30.2.28:9081/SERV_VALIDADOR_FIRMA_ELECT.svc";

            $soapService = new SoapSriService($wsd, $wdsURL);

            // Verificar conexión con el servicio SOAP
            if (!$soapService->isConnected()) {
                // Si no hay conexión, intentar actualizar la firma en la BD
              //  $resp = $this->empresaService->updateFirma($empresa, $firmaBase64, $validated['password']);

                return response()->json([
                    'success' => true,
                    'message' => 'Archivo guardado pero no se pudo conectar al servicio SOAP',
                    'procesoFirma' => '',
                    'path' => $filePath
                ], 200);
            }

            // Enviar la firma al servicio SOAP
            \Log::info('Enviando datos de firma al servicio', [
                'empresa_id' => $validated['empresa_id'],
                'opcion' => $validated['opcion']
            ]);

            $tiempoEnvioSRI = microtime(true);
            $resultadoSoap = $soapService->enviarFirma($firma);
            $tiempoPostAPI = microtime(true);

            \Log::info('Respuesta recibida del servicio', [
                'tiempo_procesamiento' => round(($tiempoPostAPI - $tiempoEnvioSRI) * 1000, 2) . ' ms',
                'resultado' => $resultadoSoap['success'] ?? false
            ]);

            // También actualizar la base de datos
            try {
              //  $this->empresaService->updateFirma($empresa, $firmaBase64, $validated['password']);
            } catch (\Exception $e) {
                \Log::warning('Error al actualizar la firma en la base de datos', [
                    'error' => $e->getMessage()
                ]);
                // No fallamos aquí, ya que el archivo se guardó correctamente
            }

            // Agregar información sobre el archivo guardado a la respuesta
            $resultadoSoap['file_path'] = $filePath;

            return response()->json($resultadoSoap, 201);

        } catch (\Exception $e) {
            \Log::error('Error al procesar la firma electrónica', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al procesar la firma electrónica',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * @OA\Post(
     *     path="/actualizarLogo",
     *     summary="Actualizar logo de empresa",
     *     description="Actualiza el logo de una empresa",
     *     tags={"Empresa"},
     *     security={{"oauth2": {}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             required={"logo", "extension", "empresa_id"},
     *             @OA\Property(
     *                 property="logo",
     *                 type="string",
     *                 format="base64",
     *                 example="data:image/png;base64,..."
     *             ),
     *             @OA\Property(
     *                 property="extension",
     *                 type="string",
     *                 enum={"jpg", "jpeg", "png"},
     *                 example="png"
     *             ),
     *             @OA\Property(
     *                 property="empresa_id",
     *                 type="string",
     *                 format="uuid",
     *                 example="123e4567-e89b-12d3-a456-426614174000"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Logo actualizado exitosamente",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Logo actualizado exitosamente"
     *             ),
     *             @OA\Property(
     *                 property="data",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Error de validación",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Los datos proporcionados no son válidos"
     *             ),
     *             @OA\Property(
     *                 property="errors",
     *                 type="object"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Empresa no encontrada",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="Empresa no encontrada"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="No autorizado",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(
     *                 property="message",
     *                 type="string",
     *                 example="No autorizado"
     *             )
     *         )
     *     )
     * )
     */
    public function actualizarLogo(Request $request)
    {


        $validated = $request->validate([
            'logo' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $value) &&
                        !preg_match('/^[a-zA-Z0-9\/+]*={0,2}$/', $value)) {
                        $fail('El logo debe ser una imagen en formato base64 válido (PNG, JPEG, JPG).');
                    }
                }
            ],
            'empresa_id' => [
                'required',
                'string',
                'uuid',
                'exists:empresas,uuid'
            ],
            'opcion' => 'required|integer|in:0,1'
        ], [
            'logo.required' => 'El logo es requerido',
            'empresa_id.required' => 'El ID de empresa es requerido',
            'empresa_id.uuid' => 'El ID de empresa debe ser un UUID válido',
            'empresa_id.exists' => 'La empresa no existe'
        ]);

        $decodedData = base64_decode($validated['logo'], true);
        if ($decodedData === false) {
            return response()->json([
                'success' => false,
                'error' => 'La firma no está en formato base64 válido'
            ], 400);
        }
        $wsd = "http://172.30.2.28:9082/SERV_GUARDA_LOGO_EMPRE.svc?singleWsdl";
        $wdsURL = "http://172.30.2.28:9082/SERV_GUARDA_LOGO_EMPRE.svc";

        $soapService = new SoapSriService($wsd,$wdsURL);
        Log::error('trama recibida', [
            'status' => true,
            'message' => $wsd,
            'error' => $wsd,
            'informacion' =>$soapService
        ]);
        if (!$soapService->isConnected()) {
            return response()->json([
                'success' => false,
                'error' => $soapService
            ], 500);
        }

        $firma = [
            'logo' => $validated['logo'],
            'empresa_id' => $validated['empresa_id'],
            'opcion' => $validated['opcion']
        ];
        $tiempoEnvioSRI = microtime(true);
        $resultadoSoap = $soapService->enviarLogo($firma);
        $tiempoPostAPI = microtime(true);
        return response()->json($resultadoSoap, 201);
    }
}
