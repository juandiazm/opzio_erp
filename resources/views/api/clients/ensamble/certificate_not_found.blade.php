@extends('layouts.app')
@section('app-header')
    <title>App Name</title>
@endsection
@section('app-content')
<div id="container" class="d-flex justify-content-center align-items-center" style="margin: 0; padding:0; width:100vw; height:100vh;">
    <div id="centered-container" style="text-align: center;">
        <img src="/images/api/clients/ensamble/diploma_logo.jpg" style="width: 250px; margin: 0 auto; padding:0; display:block;" alt="">
        <p style="font-size: 35px;margin:0; padding:0; color:#DA2029; font-weight:bold;  margin-bottom: 10px;"><i class="fa-solid fa-circle-exclamation"></i><br>No encontramos tu certificado</p>
        <p style="font-size: 22px;margin:0; padding:0; color:#3A4149;  margin-bottom: 23px;">Verifica tu número de identificación inscrito en el curso.<br></p>
        <a href="/api/clients/ensamble/certificate/get"><button style="background-color: #3A4149; color:#fff; border-radius:2px; border:none; padding:5px 15px;font-size:20px;">Volver</button></a>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('head title', window.parent.document).text('Emprendamos Juntos');
        
    });
</script>
@endsection