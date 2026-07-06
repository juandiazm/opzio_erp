@extends('client.layouts.app')
@section('component_title', 'Mi Empresa')
@section('client-app-header')
<script>
    var current_client = @json(session('client_user')['active_client']);
</script>
<script src="{{ asset('js/client/companies/companies.js') }}" defer></script>
<!-- Styles -->
<link href="{{ asset('css/client/companies/companies.css') }}" rel="stylesheet">
@endsection
@section('client-app-content')
<div id="companies-container">
    <div id="update-inputs-container" class="row m-0 p-0 w-100">
        <div class="col-12 d-flex flex-column justify-content-center" id="header-container">
            <div class="row justify-content-center">
                <div class="col-4">
                    <div class="d-flex justify-content-center">
                        <div class="multimedia-input-container">
                            <div id="update-client-img-container" class="image-container d-flex justify-content-center" style="background-image: url('{{ asset('images/erp/clients/'.session('client_user')['active_client']['photo']) }}')">
                                <input type="file" name="photo" id="update-client-img" class="d-none input_image" accept="image/*">
                            </div>
                            <i class="fa-solid fa-plus image-plus-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="col-5 align-self-center">
                    <div class="row">
                        <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                            <label for="clientname" class="input-title align-self-center">ID Cliente</label>
                            <p id="update-client-unique-id" class="m-0 p-0 align-self-center">{{ session('client_user')['active_client']['unique_id'] }}</p>
                        </div>
                        <div class="input-container col-12 d-flex" title="ID del usuario">
                            <label for="clientname" class="input-title align-self-center">Verificación</label>
                            <div class="input-value align-self-center" id="update-client-verification" value="1">
                                @if(session('client_user')['active_client']['verified'])
                                <i class="verification-input-icon fa-solid fa-medal enabled" value="1"></i>
                                @else
                                <i class="verification-input-icon fa-solid fa-ban disabled" value="0"></i>
                                @endif
                            </div>
                        </div>
                        <div class="input-container col-12 d-flex" title="ID del usuario">
                            <label for="clientname" class="input-title align-self-center">Estado</label>
                            <div class="toggle-container row" value="1" id="update-client-state">
                                @if(session('client_user')['active_client']['active'])
                                <div class="toggle-value d-flex justify-content-center col-12" value="1">
                                    <p>Activo</p>
                                </div>
                                @else
                                <div class="toggle-value d-flex justify-content-center col-12" value="0">
                                    <p>Inactivo</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="ID del usuario">
                    <label for="clientname" class="input-title align-self-center">Cliente</label>
                    <p id="update-client-name" class="input-value form-control align-self-center">{{ session('client_user')['active_client']['name'] }}</p>
                </div>
                <div class="input-container col-12 d-flex" title="Identificación del usuario">
                    <label for="clientname" class="input-title align-self-center">Tipo ID</label>
                    <select class="form-select input-value align-self-center" id="update-client-id-type" name="identification_type" value="{{ session('client_user')['active_client']['identification_type'] }}">
                        <option value="0"{{ session('client_user')['active_client']['identification_type']==0?' selected':'' }}>Nit</option>
                        <option value="1"{{ session('client_user')['active_client']['identification_type']==1?' selected':'' }}>Cédula</option>
                        <option value="2"{{ session('client_user')['active_client']['identification_type']==2?' selected':'' }}>Pasaporte</option>
                        <option value="3"{{ session('client_user')['active_client']['identification_type']==3?' selected':'' }}>Cédula extranjera</option>
                    </select>
                </div>
                <div class="input-container col-12 d-flex" title="Apellido/s del usuario">
                    <label for="clientname" class="input-title align-self-center">Identificación</label>
                    <p id="update-client-identification" class="input-value form-control align-self-center">{{ session('client_user')['active_client']['identification'] }}</p>
                </div>
                <div class="input-container col-12 d-flex" title="País">
                    <label for="countries" class="input-title align-self-center">País</label>
                    <div class="crud-input-container input-value getter" prefix="/client/getters/country/">
                        <div class="crud-input-selected-container d-flex justify-content-between" id="update-client-country" item-id="{{ session('client_user')['active_client']['country']['id'] }}">
                            <input type="text" class="crud-current-selected-input align-self-center" placeholder="Colombia" value="{{ session('client_user')['active_client']['country']['name'] }}">
                            <i class="crud-input-arrow fa-solid fa-chevron-down align-self-center"></i>
                        </div>
                        <ul class="crud-list closed scrollable">
                            <li class="crud-item-add d-flex justify-content-between">
                                <input type="text" class="crud-item-add-input align-self-center" placeholder="Agregar">
                                <i class="crud-item-add-icon fa-solid fa-plus align-self-center"></i>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col-12 col-md-6">
            <div class="row w-100 p-0 m-0">
                <div class="input-container col-12 d-flex" title="Correo del usuario">
                    <label for="clientname" class="input-title align-self-center">Dirección</label>
                    <input type="text" id="update-client-address" class="input-value form-control align-self-center" name="address" placeholder="cll 8 # 32 - 52" value="{{ session('client_user')['active_client']['address'] }}">
                </div>
                <div class="input-container col-12 d-flex" title="Correo del usuario">
                    <label for="clientname" class="input-title align-self-center">Teléfono</label>
                    <input type="number" id="update-client-phone" class="input-value form-control align-self-center" name="phone" placeholder="3002583697" value="{{ session('client_user')['active_client']['phone'] }}">
                </div>
                <div class="input-container col-12 d-flex" title="Contraseña del usuario">
                    <label for="clientname" class="input-title align-self-center">Correo</label>
                    <input type="email" id="update-client-email" class="input-email input-value form-control align-self-center" name="email" placeholder="google@gmail.com" value="{{ session('client_user')['active_client']['email'] }}">
                </div>
                <div class="input-container col-12 d-flex">
                    <label for="clientname" class="input-title align-self-center">Sector</label>
                    <div class="crud-input-container input-value getter" prefix="/client/getters/sector/">
                        <div class="crud-input-selected-container d-flex justify-content-between" id="update-client-sector" item-id="{{ session('client_user')['active_client']['sector']==null?'':(session('client_user')['active_client']['sector']['id']) }}">
                            <input type="text" class="crud-current-selected-input align-self-center" placeholder="Servicios Financieros" value="{{ session('client_user')['active_client']['sector']==null?'':(session('client_user')['active_client']['sector']['name']) }}">
                            <i class="crud-input-arrow fa-solid fa-chevron-down align-self-center"></i>
                        </div>
                        <ul class="crud-list closed scrollable">
                            <li class="crud-item-add d-flex justify-content-between">
                                <input type="text" class="crud-item-add-input align-self-center" placeholder="Agregar">
                                <i class="crud-item-add-icon fa-solid fa-plus align-self-center"></i>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <button class="btn btn-secondary" id="update-client-button">Actualizar</button>
    <nav>
        <div class="nav nav-tabs sub-nav-tabs" id="sub-nav-tab" role="tablist">
            <button class="nav-link active" id="sub-nav-documents-tab" data-bs-toggle="tab" data-bs-target="#sub-nav-documents" type="button" role="tab" aria-controls="sub-nav-documents" aria-selected="true">Documentos</button>
        </div>
    </nav>
    <div class="tab-content" id="sub-nav-tabContent">
        <div class="tab-pane fade show active" id="sub-nav-documents" role="tabpanel" aria-labelledby="sub-nav-documents-tab">
            <div id="client-documents-add-container" class="row">
                <div class="col-1 input-container d-flex">
                    <p class="client-document-input-title align-self-end">Nombre</p>
                </div>
                <div class="col-3 input-container d-flex">
                    <input type="text" name="" class="client-document-input-name align-self-end input-value form-control" placeholder="Contrato confidencialidad">
                </div>
                <div class="col-6">
                    <input type="file" class="client-document-input-file form-control" name="file" placeholder="Archivo..." aria-label="Archivo" aria-describedby="basic-addon1" accept=".pdf,.docx,.xlsx,.pptx">
                </div>
                <button class="col-2 btn btn-secondary" id="add-client-documens-button">Agregar</button>
            </div>
            <table id="client-documents-table" class="table table-sm align-middle w-100">
                <thead>
                    <tr>
                        <th scope="col" class="text-left">Nombre</th>
                        <th scope="col" class="text-left">Archivo</th>
                        <th scope="col" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody id="client-documents-table-body">
                    
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
