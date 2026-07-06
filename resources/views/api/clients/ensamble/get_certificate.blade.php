@extends('layouts.app')
@section('app-header')
@endsection
@section('app-content')
<div id="container" class="d-flex justify-content-center align-items-center" style="margin: 0; padding:0; width:100vw; height:100vh;">
    <div id="centered-container" style="text-align: center;">
        <img src="/images/api/clients/ensamble/diploma_logo.jpg" style="width: 200px; margin: 0 auto; padding:0; display:block;" alt="">
        <p style="font-size: 35px;margin:0; padding:0; color:#3A4149;">Digita tu número de identificación</p>
        <input type="number" name="id" id="id" style="width: 300px; margin: 0 auto; padding:0; display:block; margin: 10px auto 10px auto; border: 1px solid #DA2029; border-radius: 5px; padding: 5px 10px; font-size: 20px; color: #3A4149; text-align:center;" placeholder="Identificación..." autofocus>
        <button style="background-color: #DA2029; color:#fff; border-radius:2px; border:none; padding:10px 25px;font-size:20px; border-radius: 10px;"><i class="fa-solid fa-download"></i> Descargar</button>
    </div>
</div>
<script>
    $(document).ready(function(){
        $('head title', window.parent.document).text('Emprendamos Juntos');
        $('button').click(function(){
            var id = $('#id').val();
            //just numbers on id variable
            id = id.replace(/[^0-9]/g,'');
            if(id == ''){
                swallMessage(
                    'Error'
                    , 'Debes ingresar tu número de identificación'
                    , 'error'
                    , null
                    , null
                    , 3000
                    , null
                    , null
                );
            }else{
                $('#id').val('');
                window.location.href = '/api/clients/ensamble/certificate/download/'+id;
                setTimeout(() => {
                    window.location.href = 'https://www.ensamblexl.co/emprendamos-juntos';
                }, 3000);
            }
        });
    });
</script>
@endsection