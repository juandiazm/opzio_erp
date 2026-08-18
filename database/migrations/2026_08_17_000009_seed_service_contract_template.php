<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class SeedServiceContractTemplate extends Migration
{
    public function up()
    {
        $typeName = 'Prestación de servicios';
        $templateName = 'Contrato de prestación de servicios - infraestructura y soporte';
        $now = now();
        $typeId = DB::table('contract_types')->where('name', $typeName)->value('id');

        if (!$typeId) {
            $typeId = DB::table('contract_types')->insertGetId([
                'name' => $typeName,
                'description' => 'Contrato para servicios de alojamiento de infraestructura, ciberseguridad y soporte técnico especializado.',
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $variables = [
            ['key' => 'custom.contract_number', 'label' => 'Número del contrato', 'type' => 'text', 'default_value' => '003-2026', 'required' => true],
            ['key' => 'custom.contract_duration', 'label' => 'Duración visible', 'type' => 'text', 'default_value' => 'SEIS (6) MESES Y CUATRO (4) DÍAS', 'required' => true],
            ['key' => 'custom.contract_object', 'label' => 'Objeto del contrato', 'type' => 'text', 'default_value' => 'PRESTAR EL SERVICIO DE ALOJAMIENTO DE INFRAESTRUCTURA, CIBERSEGURIDAD Y SOPORTE TÉCNICO ESPECIALIZADO.', 'required' => true],
            ['key' => 'custom.contractant_representative_title', 'label' => 'Tratamiento del representante del contratante', 'type' => 'text', 'default_value' => 'señor', 'required' => true],
            ['key' => 'custom.contractant_representative_name', 'label' => 'Representante legal del contratante', 'type' => 'text', 'default_value' => 'OMAR HERNÁN BAUTISTA', 'required' => true],
            ['key' => 'custom.contractant_representative_identification', 'label' => 'Identificación del representante del contratante', 'type' => 'text', 'default_value' => '19.203.655', 'required' => true],
            ['key' => 'custom.contractant_city', 'label' => 'Ciudad del contratante', 'type' => 'text', 'default_value' => 'Bogotá D.C.', 'required' => true],
            ['key' => 'custom.contractant_domain', 'label' => 'Dominio del contratante', 'type' => 'text', 'default_value' => 'www.ohbltda.com', 'required' => true],
            ['key' => 'custom.contractor_name', 'label' => 'Nombre del contratista', 'type' => 'text', 'default_value' => 'OPZIO S.A.S.', 'required' => true],
            ['key' => 'custom.contractor_identification', 'label' => 'NIT del contratista', 'type' => 'text', 'default_value' => '902.086.745-1', 'required' => true],
            ['key' => 'custom.contractor_representative_title', 'label' => 'Tratamiento del representante del contratista', 'type' => 'text', 'default_value' => 'señora', 'required' => true],
            ['key' => 'custom.contractor_representative_name', 'label' => 'Representante legal del contratista', 'type' => 'text', 'default_value' => 'MARÍA FERNANDA FRANCO ACOSTA', 'required' => true],
            ['key' => 'custom.contractor_representative_identification', 'label' => 'Identificación del representante del contratista', 'type' => 'text', 'default_value' => '1.073.243.353', 'required' => true],
            ['key' => 'custom.contractor_address', 'label' => 'Dirección del contratista', 'type' => 'text', 'default_value' => 'Diagonal 23c bis 88b 10', 'required' => true],
            ['key' => 'custom.contractor_phone', 'label' => 'Teléfono del contratista', 'type' => 'text', 'default_value' => '(+57) 314 5452826 - 3145433746', 'required' => true],
            ['key' => 'custom.contractor_city', 'label' => 'Ciudad del contratista', 'type' => 'text', 'default_value' => 'Bogotá D.C.', 'required' => true],
            ['key' => 'custom.contractor_email', 'label' => 'Correo del contratista', 'type' => 'email', 'default_value' => 'legal@opzio.co', 'required' => true],
            ['key' => 'custom.contractor_domain', 'label' => 'Dominio del contratista', 'type' => 'text', 'default_value' => 'www.opzio.co', 'required' => true],
            ['key' => 'custom.start_date_text', 'label' => 'Fecha de inicio visible', 'type' => 'text', 'default_value' => '01 DE AGOSTO DE 2026', 'required' => true],
            ['key' => 'custom.end_date_text', 'label' => 'Fecha de finalización visible', 'type' => 'text', 'default_value' => '05 DE FEBRERO DE 2027', 'required' => true],
            ['key' => 'custom.payment_year', 'label' => 'Año del valor inicial', 'type' => 'number', 'default_value' => '2026', 'required' => true],
            ['key' => 'custom.initial_period_cost', 'label' => 'Costo del periodo inicial', 'type' => 'text', 'default_value' => 'no generará costo alguno para las partes', 'required' => true],
            ['key' => 'custom.annual_renewal_percentage', 'label' => 'Porcentaje de renovación anual', 'type' => 'number', 'default_value' => '20', 'required' => true],
            ['key' => 'custom.payment_deadline', 'label' => 'Fecha límite de pago', 'type' => 'text', 'default_value' => 'el 20 de febrero de cada año de vigencia', 'required' => true],
            ['key' => 'custom.signature_date', 'label' => 'Fecha de firma', 'type' => 'text', 'default_value' => '31 días del mes de julio de 2026', 'required' => true],
        ];

        $content = <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; color: #111; font-size: 12px; line-height: 1.45; text-align: justify;">
    <table style="width: 100%; border-collapse: collapse; border: 1px solid #222; margin: 0 0 25px 0;">
        <tbody>
            <tr>
                <th colspan="2" style="border: 1px solid #222; background-color: #d0cece; padding: 7px; text-align: center; font-size: 15px; font-weight: bold;">CONTRATO DE PRESTACIÓN DE SERVICIOS No. {{custom.contract_number}}</th>
            </tr>
            <tr>
                <td style="width: 34%; border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">CONTRATANTE:</td>
                <td style="border: 1px solid #222; padding: 6px; font-weight: bold;">{{client.complete_name}}<br><span style="font-weight: normal;">NIT: {{client.identification}}</span></td>
            </tr>
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">CONTRATISTA:</td>
                <td style="border: 1px solid #222; padding: 6px; font-weight: bold;">{{custom.contractor_name}}<br><span style="font-weight: normal;">NIT: {{custom.contractor_identification}}</span></td>
            </tr>
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">OBJETO:</td>
                <td style="border: 1px solid #222; padding: 6px;">{{custom.contract_object}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">DURACIÓN:</td>
                <td style="border: 1px solid #222; padding: 6px;">{{custom.contract_duration}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">FECHA DE INICIO:</td>
                <td style="border: 1px solid #222; padding: 6px;">{{custom.start_date_text}}</td>
            </tr>
            <tr>
                <td style="border: 1px solid #222; background-color: #d0cece; padding: 6px; font-weight: bold;">FECHA DE FINALIZACIÓN:</td>
                <td style="border: 1px solid #222; padding: 6px;">{{custom.end_date_text}}</td>
            </tr>
        </tbody>
    </table>

    <p>Entre los suscritos, por una parte, <strong>{{client.complete_name}}</strong>, identificada con el NIT {{client.identification}}, representada legalmente por el {{custom.contractant_representative_title}} <strong>{{custom.contractant_representative_name}}</strong>, identificado con cédula de ciudadanía {{custom.contractant_representative_identification}}, quien para efectos del presente contrato se denominará <strong>EL CONTRATANTE</strong>, y por la otra <strong>{{custom.contractor_name}}</strong>, identificada con el NIT {{custom.contractor_identification}}, representada legalmente por la {{custom.contractor_representative_title}} <strong>{{custom.contractor_representative_name}}</strong>, identificada con cédula de ciudadanía {{custom.contractor_representative_identification}}, quien en adelante se denominará <strong>EL CONTRATISTA</strong>, hemos convenido en celebrar el presente Contrato de Prestación de Servicios, el cual se regirá por las disposiciones del Código Civil, el Código de Comercio y en especial por las siguientes cláusulas:</p>

    <p><strong>CLÁUSULA PRIMERA – OBJETO:</strong> {{custom.contract_object}}</p>

    <p><strong>CLÁUSULA SEGUNDA – ACTIVIDADES:</strong> El alcance de los servicios y las actividades a cargo del CONTRATISTA serán los estipulados a continuación:</p>
    <p><strong>CARACTERÍSTICAS DEL SERVICIO:</strong></p>
    <ul>
        <li><strong>Ciberseguridad:</strong> Implementación de protocolos para prevenir, detectar y mitigar riesgos informáticos que atenten contra la confidencialidad, integridad y disponibilidad de la información, de conformidad con lo estipulado para la prevención de las conductas tipificadas en la Ley 1273 de 2009.</li>
        <li><strong>Soporte técnico:</strong> Mantenimiento preventivo, correctivo y mesa de ayuda.</li>
    </ul>

    <p><strong>CLÁUSULA TERCERA – IDONEIDAD Y CAPACIDAD:</strong> EL CONTRATISTA declara y garantiza que cuenta con la plena capacidad jurídica, técnica, financiera, operativa y administrativa para la ejecución del presente contrato. Asimismo, manifiesta que dispone del personal calificado, la experiencia específica, las licencias, los permisos y las herramientas tecnológicas necesarias para el debido cumplimiento del objeto contractual.</p>

    <p><strong>CLÁUSULA CUARTA – AUTONOMÍA Y APORTE DE RECURSOS:</strong> EL CONTRATISTA en su calidad de prestador de servicios independiente, ejecutará el objeto del presente contrato con total autonomía técnica, administrativa y operativa. En consecuencia, se obliga a proveer por su propia cuenta y riesgo todos los recursos humanos, técnicos, físicos, herramientas y demás elementos que se requieran para el debido, oportuno y satisfactorio cumplimiento de las actividades contratadas.</p>

    <p><strong>CLÁUSULA QUINTA – OBLIGACIONES DEL CONTRATANTE:</strong> Constituyen obligaciones a cargo de EL CONTRATANTE las siguientes:</p>
    <ol>
        <li>Reconocer plena autonomía técnica, administrativa y operativa al CONTRATISTA para la efectiva ejecución del objeto contractual, absteniéndose de realizar actos que configuren subordinación laboral.</li>
        <li>Suministrar al CONTRATISTA, de manera oportuna, veraz y completa, toda la información, requerimientos de negocio, manuales de marca o especificaciones necesarias e imprescindibles para el cumplimiento del objeto contractual.</li>
        <li>Pagar oportunamente el valor pactado por los servicios prestados, conforme a las condiciones, plazos e hitos de entrega establecidos en este contrato y previa presentación de la cuenta de cobro o factura correspondiente.</li>
        <li>Proveer las credenciales de acceso, entornos de desarrollo, repositorios de código, servidores, API o herramientas de terceros que sean de propiedad del CONTRATANTE y que resulten estrictamente necesarios para el desarrollo y soporte del dominio web <a href="https://www.ohbltda.com" target="_blank" rel="noopener">{{custom.contractant_domain}}</a>.</li>
        <li>Designar a una persona de su equipo técnico o de operaciones para que actúe como canal oficial de comunicación, reportes y aprobaciones, centralizando los requerimientos dirigidos al CONTRATISTA.</li>
        <li>Garantizar que toda la información, bases de datos, logotipos o insumos que entregue al CONTRATISTA para ser incorporados en el dominio web son de su propiedad o cuenta con las licencias correspondientes, eximiendo al CONTRATISTA de cualquier responsabilidad por infracción de derechos de autor de terceros.</li>
        <li>Notificar de manera clara, detallada y a través de los canales digitales acordados, cualquier incidente, error informático (bug) o caída del servicio en el dominio web <a href="https://www.ohbltda.com" target="_blank" rel="noopener">{{custom.contractant_domain}}</a> para que el CONTRATISTA pueda ejecutar las actividades de soporte técnico correspondientes.</li>
    </ol>

    <p><strong>CLÁUSULA SEXTA - OBLIGACIONES DEL CONTRATISTA:</strong> Además de las obligaciones que emanan de la naturaleza del presente Contrato, y de las derivadas de las disposiciones legales, EL CONTRATISTA se obliga para con EL CONTRATANTE a:</p>
    <ol>
        <li>Ejecutar bajo su responsabilidad todas las labores que sean necesarias para el debido y satisfactorio cumplimiento del objeto contractual, suministrando tanto el personal como los materiales necesarios para la ejecución del mismo de acuerdo con las actividades descritas.</li>
        <li>Informar oportunamente sobre cualquier dificultad o inconveniente que se presente durante el desarrollo del presente Contrato.</li>
        <li>Seleccionar, contratar, dirigir, controlar y supervisar el personal necesario para la ejecución del Contrato, controlando su funcionamiento y administración, garantizando en todo caso la calificación del mismo respecto de las tareas que se le encomienden.</li>
        <li>Cumplir con todas las obligaciones laborales y en materia de seguridad social, como quiera que es quien tiene la calidad de empleador y patrono del personal con que dispone para adelantar el cumplimiento del Contrato.</li>
        <li>Presentar la correspondiente factura como soporte para cada uno de los pagos, indicando el presente Contrato y de acuerdo con lo prescrito por las normas tributarias colombianas. El CONTRATISTA entiende y acepta la imposibilidad del CONTRATANTE de recibir sus facturas en caso de no cumplir con lo establecido en este numeral.</li>
        <li>Constituir y mantener vigentes las pólizas que se indican en el presente Contrato.</li>
        <li>Tanto el mismo personal del CONTRATISTA como el requerido para el cabal desarrollo del contrato, deberán guardar absoluta confidencialidad acerca de la información que por razones de su oficio llegaren a conocer del CONTRATANTE, como de sus operaciones y procesos internos, de tal manera que únicamente con la finalidad de dar pleno cumplimiento al objeto del Contrato, obligándose a su total reserva. Esta obligación se extenderá desde la fecha de terminación del Contrato por un término indefinido y cobijará a todos los colaboradores que el CONTRATISTA utilice para el cumplimiento del objetivo contractual pactado.</li>
        <li>Toda la información escrita o documental que le sea suministrada, deberá devolverla al finalizar el Contrato. En caso de que la misma no sea susceptible de devolución, deberá destruirla y queda obligado a certificar lo anterior cuando se lo solicite el CONTRATANTE. La obligación extendida de confidencialidad descrita en la parte final del ítem anterior, es aplicable a cualquier divulgación de información de valor o confidencial que sea conocida por el CONTRATISTA.</li>
        <li>No hacer divulgación de ninguna de las relaciones jurídicas y comerciales, que tiene o llegará a tener con el CONTRATANTE, salvo que éste otorgue su autorización previa y por escrito en cada caso o evento. Para estos efectos se entenderán como eventos de divulgación pública, sin limitarse a ellos, las menciones o avisos en medios masivos de comunicación, vallas, correo electrónico, páginas web, comunicados o ruedas de prensa, información relevante como el diseño y desarrollo del dominio web del CONTRATANTE, etc. El desconocimiento de esta obligación por parte del CONTRATISTA se entenderá como incumplimiento grave del Contrato y facultará a EL CONTRATANTE a ejercer todas las acciones derivadas de dicho incumplimiento.</li>
    </ol>

    <p><strong>CLÁUSULA SÉPTIMA – DURACIÓN:</strong> El presente Contrato tendrá una duración de {{custom.contract_duration}} contados a partir del {{custom.start_date_text}} hasta el {{custom.end_date_text}} y se prorrogará automáticamente por períodos de un (1) año, si ninguna de las partes manifiesta su intención de terminarlo con antelación no inferior a treinta (30) días.</p>

    <p><strong>CLÁUSULA OCTAVA – MODIFICACIONES AL CONTRATO:</strong> Cualquier modificación, adición, prórroga o aclaración de los términos, plazos, costos o alcances pactados en el presente contrato podrá ser solicitada por cualquiera de las partes. Para tal efecto, la parte interesada deberá presentar una solicitud formal por escrito a la otra parte. Toda modificación requerirá el mutuo acuerdo de las partes y se formalizará exclusivamente mediante la suscripción de un Otrosí firmado por los representantes legales.</p>
    <p><strong>PARÁGRAFO PRIMERO:</strong> En los eventos de modificaciones al presente contrato por mutuo acuerdo entre el CONTRATISTA y el CONTRATANTE prevalecerá lo acordado entre las partes y se modificarán las condiciones estipuladas en el presente contrato.</p>
    <p><strong>PARÁGRAFO SEGUNDO - COSTOS ADICIONALES:</strong> Toda nueva funcionalidad o requerimiento técnico que no se encuentre expresamente contemplado en la propuesta inicial contemplada, será facturado de manera independiente por el CONTRATISTA, previa presentación y aprobación de una cotización complementaria por parte del CONTRATANTE. Dichas adiciones no deberán afectar, alterar ni desmejorar el correcto funcionamiento de las características ya existentes en el presente contrato.</p>

    <p><strong>CLÁUSULA NOVENA– VALOR Y FORMA DE PAGO:</strong> El periodo inicialmente pactado en el presente contrato {{custom.initial_period_cost}}. No obstante, para el año {{custom.payment_year}} el valor de la página es de <strong>SEIS MILLONES DE PESOS M/CTE ($6.000.000)</strong> valor que será tenido en cuenta en caso de operar la prórroga automática junto con las adiciones suscritas, cada anualidad prorrogada tendrá un costo equivalente al {{custom.annual_renewal_percentage}}% del valor comercial del software vigente al momento de la renovación. Este monto se pagará una sola vez durante la anualidad dentro de los 15 días calendario siguientes a la fecha de la prórroga, fijándose como fecha límite de pago {{custom.payment_deadline}}.</p>
    <p><strong>PARÁGRAFO:</strong> La prórroga automática y el cobro del costo señalado en la presente cláusula estarán sujetos al resultado favorable de la validación y evaluación técnica y financiera anual del software. La decisión respecto a la aprobación de dicha validación y la procedencia de la renovación será notificada por escrito a la otra parte con mínimo UN (1) mes de antelación a la fecha de vencimiento o terminación de la anualidad correspondiente.</p>

    <p><strong>CLÁUSULA DÉCIMA – NATURALEZA Y AUTONOMÍA:</strong> Para todos los efectos legales el CONTRATISTA actuará de manera independiente, por lo cual deberá ejecutar el objeto del presente Contrato íntegramente con sus propios medios, con autonomía técnica y jurídica. El personal que el CONTRATISTA emplee en la ejecución del objeto será de su libre nombramiento y remoción, estará bajo su inmediata subordinación y dependencia y estarán exclusivamente a su cargo los salarios, pagos parafiscales, seguros, riesgos, prestaciones sociales, indemnizaciones, y cualquier otra obligación que se derive a su vinculación laboral, sin que el CONTRATANTE tenga que asumir ninguna obligación, ni soportar carga ni riesgo alguno por este concepto. Así mismo, el CONTRATISTA se obliga a suministrar a sus trabajadores la dotación de trabajo de acuerdo con los términos de la Ley y los elementos de protección indispensables para realizar su labor en condiciones seguras.</p>
    <p><strong>PARÁGRAFO PRIMERO:</strong> EL CONTRATISTA se obliga a afiliar al personal que emplee en la ejecución del Contrato al sistema de seguridad social integral, es decir, tanto el sistema general de pensiones, como al sistema de seguridad social en salud y al sistema general de riesgos profesionales. Así mismo, se obliga a cancelar oportunamente todos los aportes, contribuciones y cargos, para mantener todas las obligaciones anteriores al día.</p>
    <p><strong>PARÁGRAFO SEGUNDO:</strong> EL CONTRATANTE no podrá emplear en la ejecución del Contrato, ni dentro del 1 año siguiente a su terminación, como empleados suyos a trabajadores que estén al servicio del CONTRATISTA y éste, a su vez, se obliga a no emplear a su servicio al personal que labore para el CONTRATANTE. Adicionalmente, el CONTRATISTA suministrará a EL CONTRATANTE los perfiles de los empleados que van a intervenir en este contrato.</p>

    <p><strong>CLÁUSULA DÉCIMA PRIMERA – PROHIBICIÓN DE CESIÓN Y SUBCONTRATACIÓN:</strong> EL CONTRATISTA no podrá ceder los derechos y obligaciones, ni subcontratar total o parcialmente el cumplimiento del objeto sin previo consentimiento escrito de EL CONTRATANTE, y en todo caso, de obtener la autorización, seguirá siendo responsable solidariamente por su cumplimiento, obligándose a pactar en los contratos o acuerdos que celebre con sus subcontratistas para la prestación de los servicios, todas las obligaciones por él contraídas en virtud de la celebración de este Contrato, en materias: laboral; de seguridad social, seguridad industrial, responsabilidad social, ambiental, confidencialidad y propiedad intelectual.</p>

    <p><strong>CLÁUSULA DÉCIMA SEGUNDA – INDEMNIDAD Y LIMITACIÓN DE RESPONSABILIDAD:</strong> Las partes acuerdan que cada una se compromete a indemnizar y mantener indemne a la otra parte, sus directores, empleados, representantes y agentes, frente a cualquier reclamo, demanda, daño, perjuicio, costo, gasto, honorarios legales o cualquier otra responsabilidad que pueda surgir a raíz de:</p>
    <ol>
        <li><strong>Incumplimiento de las obligaciones contractuales:</strong> Cualquier incumplimiento de las obligaciones asumidas por cualquiera de las partes en virtud de este contrato.</li>
        <li><strong>Acciones u omisiones de cualquiera de las partes que resulten en daños a terceros:</strong> Incluidas las demandas o reclamaciones legales por responsabilidad civil, penal o administrativa derivadas de la ejecución del contrato.</li>
        <li><strong>Vulneración de derechos de propiedad intelectual o industrial:</strong> En caso de que cualquiera de las partes utilice de manera indebida marcas, patentes, derechos de autor, u otros derechos protegidos sin la debida autorización o licencia. EL CONTRATANTE asumirá de forma exclusiva la responsabilidad y mantendrá indemne al CONTRATISTA si la presunta vulneración proviene de logos, códigos, bases de datos o insumos suministrados por el propio CONTRATANTE.</li>
    </ol>
    <p><strong>PARÁGRAFO: LÍMITE DE RESPONSABILIDAD.</strong> Sin perjuicio de lo dispuesto en los numerales anteriores, las partes acuerdan expresamente que la responsabilidad total y acumulada del CONTRATISTA por cualquier concepto derivado de este contrato tendrá como tope máximo global una suma equivalente al valor total de los honorarios efectivamente percibidos por el CONTRATISTA en el marco de este acuerdo.</p>

    <p><strong>CLÁUSULA DÉCIMA TERCERA – SOLUCIÓN DE CONTROVERSIAS:</strong> Las partes acuerdan que cualquier diferencia, disputa o reclamación que surja de la interpretación, ejecución o terminación del presente contrato se resolverá bajo los principios de la buena fe, mediante el siguiente procedimiento escalonado:</p>
    <ol>
        <li><strong>Arreglo Directo:</strong> La parte afectada notificará por escrito a la otra la existencia del conflicto. Las partes dispondrán de un término de treinta (30) días calendario, contados a partir de la notificación, para intentar solucionar la controversia directamente entre sus representantes.</li>
        <li><strong>Conciliación:</strong> Si vencido el plazo anterior las partes no logran un acuerdo, la disputa se someterá a una audiencia de conciliación extrajudicial en el Centro de Conciliación de la Cámara de Comercio de Bogotá D.C.</li>
    </ol>

    <p><strong>CLÁUSULA DÉCIMA CUARTA – TERMINACIÓN:</strong> En adición a las causales de la Ley y a las demás establecidas en el Contrato, el mismo se terminará por las siguientes:</p>
    <ol>
        <li>Por la completa ejecución de las obligaciones que surjan del presente Contrato.</li>
        <li>Por el incumplimiento de las partes de lo establecido en el presente Contrato.</li>
        <li>Por vencimiento del término establecido en el presente Contrato.</li>
        <li>Por mutuo acuerdo entre las partes, la cual deberá de manifestarse con una anticipación no menor de 30 días, en todo caso para el ejercicio de esta facultad la parte que pretenda la terminación unilateral del Contrato deberá acreditar el cumplimiento de las obligaciones que tiene a su cargo.</li>
    </ol>

    <p><strong>CLÁUSULA DÉCIMA QUINTA – IMPUESTO Y GRAVÁMENES.</strong> Todos los impuestos, contribuciones, tasas, tarifas, derechos y gravámenes que se llegaren a causar por razón o como consecuencia de la ejecución del Contrato, serán asumidos por la Parte que corresponda según la Ley.</p>

    <p><strong>CLÁUSULA DÉCIMA SEXTA– CLÁUSULA PENAL:</strong> En caso de incumplimiento total o parcial de cualquiera de las partes respecto a las obligaciones establecidas en este contrato, la parte incumplidora se compromete a pagar a la otra parte una penalización equivalente al 20% del valor total del contrato incluidas las prórrogas efectuadas, sin perjuicio de las acciones legales que pudieran corresponder por daños y perjuicios adicionales que pudieran derivarse del incumplimiento. La penalización será exigible a partir del momento en que la parte afectada notifique por escrito a la parte incumplidora su incumplimiento y transcurra el plazo otorgado para la subsanación del mismo, el cual será de 30 días calendario desde dicha notificación. La parte incumplidora no estará sujeta al pago de la penalización si demuestra que el incumplimiento fue causado por fuerza mayor o caso fortuito, tal como se define en la legislación aplicable, y ha notificado de forma inmediata la situación que impide el cumplimiento. La presente cláusula no será aplicable si el incumplimiento es imputable a la parte afectada por un comportamiento doloso o fraudulento de la otra parte.</p>
    <p><strong>PARÁGRAFO PRIMERO:</strong> Para que sea exigible la cláusula penal ante la jurisdicción por vía ejecutiva se deberá agotar la etapa de arreglo directo.</p>
    <p><strong>PARÁGRAFO SEGUNDO:</strong> Para agotar la etapa de arreglo directo, toda controversia o diferencia que pueda surgir con ocasión de este contrato, ejecución o terminación, se procurará resolver de manera directa entre las partes. Para agotar la etapa de arreglo directo, la parte que considere que existe un conflicto lo manifestará por escrito indicando (i) los motivos de la inconformidad, (ii) las causas que originan la inconformidad, (iii) la solución propuesta al conflicto que deberá ser diferente a la terminación del contrato. Entregada esta reclamación, las partes deberán en el plazo indicado procurar el arreglo directo.</p>

    <p><strong>CLÁUSULA DÉCIMA SÉPTIMA – MÉRITO EJECUTIVO:</strong> Las partes acuerdan expresamente que el presente contrato, junto con los documentos que se deriven del mismo y los que acrediten el incumplimiento de cualquiera de las obligaciones aquí estipuladas, constituye título ejecutivo conforme a lo establecido en el artículo 422 del Código General del Proceso, y será suficiente para exigir judicialmente el cumplimiento forzado de las obligaciones aquí contraídas, así como el pago de la cláusula penal pactada, intereses y demás conceptos aplicables.</p>

    <p><strong>CLÁUSULA DÉCIMA OCTAVA – PREVALENCIA:</strong> En el evento que surja un conflicto entre una cláusula del Contrato, los términos y condiciones del CONTRATISTA y/o los documentos anexos al mismo, se preferirá la condición estipulada en el presente Contrato.</p>

    <p><strong>CLÁUSULA DÉCIMA NOVENA – FUERZA MAYOR.</strong> En caso de presentarse un evento de fuerza mayor o caso fortuito durante la ejecución del Contrato, que impida a EL CONTRATISTA la ejecución de sus obligaciones, EL CONTRATISTA pondrá en conocimiento del CONTRATANTE dicho evento dentro de las veinticuatro (24) horas siguientes al conocimiento del mismo, el cual deberá ser acreditado, y prestará toda su colaboración en la solución de la situación y en la limitación de la extensión de un eventual perjuicio derivado de la situación.</p>

    <p><strong>CLÁUSULA VIGÉSIMA – INEXISTENCIA DE RELACIÓN LABORAL.</strong> Las partes declaran expresamente que el presente contrato no genera ni generará entre ellas relación laboral alguna. EL CONTRATISTA y EL CONTRATANTE actúan con plena autonomía técnica y administrativa, asumiendo los riesgos propios de su actividad, sin estar sujeta a subordinación jurídica ni a órdenes directas por parte de EL CONTRATANTE, más allá de los lineamientos necesarios para el cumplimiento del objeto del contrato. En consecuencia, las PARTES reconocen que no tienen derecho a reclamar prestaciones sociales, aportes a seguridad social por cuenta del contratante, indemnizaciones laborales, ni ningún otro beneficio derivado de una relación de carácter laboral, asumiendo por su cuenta todas las obligaciones fiscales, parafiscales y de seguridad social que le correspondan según la ley.</p>

    <p><strong>CLÁUSULA VIGÉSIMA PRIMERA – PROPIEDAD INTELECTUAL:</strong> Las Partes reconocen y aceptan que la celebración y ejecución del presente contrato no implica la cesión, transferencia ni venta de ningún derecho patrimonial o moral de autor, ni de propiedad industrial, sobre sus respectivos activos preexistentes, de conformidad con la Ley 23 de 1982 y la Decisión Andina 351 de 1993. En consecuencia, la propiedad intelectual se regirá por las siguientes reglas:</p>
    <ul>
        <li><strong>Titularidad del CONTRATANTE:</strong> El dominio web, los logotipos, marcas, diseños, y especialmente todas las bases de datos (incluyendo información institucional, operativa o administrativa), así como cualquier contenido cargado en la infraestructura provista, son de propiedad exclusiva e inalienable de EL CONTRATANTE. EL CONTRATISTA reconoce que sobre estos activos solo ostenta un permiso de acceso y manipulación técnica, estrictamente limitado al tiempo de duración del contrato y única y exclusivamente para cumplir con las labores de alojamiento, soporte y protección.</li>
        <li><strong>Titularidad del CONTRATISTA:</strong> Todo el software, scripts, algoritmos de protección, arquitecturas de ciberseguridad, plataformas de monitoreo, herramientas de mesa de ayuda (HelpDesk) y demás metodologías o infraestructuras lógicas empleadas por EL CONTRATISTA para prestar los servicios de alojamiento y soporte técnico especializado, son de su propiedad exclusiva o cuenta con las licencias respectivas para su uso. EL CONTRATANTE no adquiere ningún derecho sobre el código fuente, la estructura ni la propiedad de estas herramientas operativas.</li>
        <li><strong>Desarrollos y Soluciones de Soporte:</strong> Cualquier parche, actualización de seguridad o configuración específica en el servidor implementada por EL CONTRATISTA como parte del soporte técnico y la ciberseguridad, se entenderá como parte integral del servicio prestado y no como una obra por encargo que deba cederse a EL CONTRATANTE, salvo que las partes acuerden expresamente y por escrito el desarrollo de un módulo de software a la medida mediante un Otrosí.</li>
    </ul>
    <p><strong>PARÁGRAFO:</strong> Cada una de las Partes mantendrá la titularidad sobre los activos intelectuales que a la firma del presente contrato son de su propiedad, y que serán utilizados en la ejecución del presente contrato. Sin embargo, el CONTRATISTA se obliga a no duplicar, reproducir o de cualquier forma realizar copias de la aplicación o de la INFORMACIÓN CONFIDENCIAL, sin el consentimiento previo y por escrito del CONTRATANTE.</p>

    <p><strong>CLÁUSULA VIGÉSIMA SEGUNDA - TRATAMIENTOS DE DATOS PERSONALES:</strong> En caso que resulte necesario para la ejecución del presente contrato que deba realizarse el tratamiento de datos personales, cada una de las Partes de manera previa, y expresa e informada autoriza a la otra Parte para que, directamente o a través de sus encargados del tratamiento de datos personales, lleve a cabo la recolección, almacenamiento, uso, circulación, supresión, transferencia y transmisión del tratamiento de sus datos personales y de los datos personales de terceros que está entregando con ocasión de la relación contractual. Esto, exclusivamente para las finalidades propias de la ejecución del contrato con las que resulten similares o análogas a este. En todo caso, las Partes conocen que a los Titulares de datos personales les asisten los derechos consignados en el artículo 8º de la Ley 1581 de 2012.</p>
    <p><strong>PARÁGRAFO PRIMERO: Confidencialidad en el Manejo de Datos Personales.</strong> Cada una de las Partes se obligan a guardar sigilo y estricta confidencialidad respecto a la información y/o Datos Personales que conozca sobre Clientes, Proveedores, Contratistas, Colaboradores de la otra Parte y terceros, a los que tenga acceso en la ejecución de este contrato, sobre el entendido de que dicha información y datos personales gozan de reserva legal y no pueden recogerse, almacenarse, darse a conocer, suprimirse, consultarse o transferirse sin previa autorización de la otra Parte, y en ningún caso, se podrán adulterar o usar para beneficio propio o de terceros. Así las cosas, cada una de las Partes se obliga en forma directa, y asume la responsabilidad de guardar el sigilo y confidencialidad que exige tal información y/o Datos personales, so pena de las consecuencias legales que amerite dicha desatención. Esta obligación subsistirá de manera indefinida a la terminación del Contrato, e incorpora a los colaboradores que utilice el Contratista para el cumplimiento del objeto contractual.</p>
    <p><strong>PARÁGRAFO SEGUNDO:</strong> Cualquier desconocimiento a las obligaciones de confidencialidad establecidas a lo largo del presente instrumento constituirá incumplimiento del Contrato, y dará lugar al inicio de proceso para declarar el cumplimiento de la cláusula penal.</p>

    <p><strong>CLÁUSULA VIGÉSIMA TERCERA– JURISDICCIÓN Y LEY APLICABLE:</strong> Las Partes establecen que el presente Contrato se regirá por lo dispuesto en las leyes colombianas, y los conflictos que resultaren entre las partes serán solucionados por los jueces de la República de Colombia Ley y Jurisdicción aplicable.</p>

    <p><strong>CLÁUSULA VIGÉSIMA CUARTA - NOTIFICACIONES:</strong> Para todos los efectos del presente Contrato, se tiene la ciudad de {{custom.contractant_city}} como el domicilio, y todas las comunicaciones que se crucen entre las Partes en desarrollo del mismo deberán ser enviadas a las siguientes direcciones:</p>
    <table style="width: 100%; border-collapse: collapse; margin: 12px 0 24px 0;">
        <tbody>
            <tr>
                <td style="width: 50%; vertical-align: top; padding: 8px 18px 8px 0;"><strong>CONTRATANTE:</strong> {{client.complete_name}}<br>Dirección: {{client.address}}<br>Teléfono: {{client.phone}}<br>Ciudad: {{custom.contractant_city}}<br>Correo: <a href="mailto:{{client.email}}">{{client.email}}</a></td>
                <td style="width: 50%; vertical-align: top; padding: 8px 0 8px 18px;"><strong>CONTRATISTA:</strong> {{custom.contractor_name}}<br>Dirección: {{custom.contractor_address}}<br>Teléfono: {{custom.contractor_phone}}<br>Ciudad: {{custom.contractor_city}}<br>Correo: <a href="mailto:{{custom.contractor_email}}">{{custom.contractor_email}}</a></td>
            </tr>
        </tbody>
    </table>

    <p><strong>CLÁUSULA VIGÉSIMA SÉPTIMA – FIRMAS:</strong> Para constancia y fe de lo expuesto, se firma en Colombia, a los {{custom.signature_date}} en dos (2) copias idénticas con destino a cada una de LAS PARTES.</p>
    <table style="width: 100%; border-collapse: collapse; margin: 30px 0 0 0;">
        <tbody>
            <tr>
                <td style="width: 50%; padding: 8px 18px 8px 0; vertical-align: top;"><strong>CONTRATANTE:</strong><br><br><br>_________________<br><strong>{{client.complete_name}}</strong><br>NIT: {{client.identification}}<br>Representante legal<br>{{custom.contractant_representative_name}}<br>C.C. No. {{custom.contractant_representative_identification}}</td>
                <td style="width: 50%; padding: 8px 0 8px 18px; vertical-align: top;"><strong>CONTRATISTA:</strong><br><br><br>_________________<br><strong>{{custom.contractor_name}}</strong><br>NIT: {{custom.contractor_identification}}<br>Representante legal<br>{{custom.contractor_representative_name}}<br>C.C. {{custom.contractor_representative_identification}}</td>
            </tr>
        </tbody>
    </table>
</div>
HTML;

        $templateExists = DB::table('contract_templates')
            ->where('contract_type_id', $typeId)
            ->where('name', $templateName)
            ->exists();

        if (!$templateExists) {
            DB::table('contract_templates')->insert([
                'contract_type_id' => $typeId,
                'name' => $templateName,
                'subject' => 'Contrato de prestación de servicios No. {{custom.contract_number}}',
                'content' => $content,
                'variables' => json_encode($variables, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'version' => 1,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down()
    {
        $typeName = 'Prestación de servicios';
        $templateName = 'Contrato de prestación de servicios - infraestructura y soporte';
        $typeId = DB::table('contract_types')->where('name', $typeName)->value('id');

        if (!$typeId) {
            return;
        }

        $templateId = DB::table('contract_templates')
            ->where('contract_type_id', $typeId)
            ->where('name', $templateName)
            ->value('id');

        if ($templateId && !DB::table('contracts')->where('contract_template_id', $templateId)->exists()) {
            DB::table('contract_templates')->where('id', $templateId)->delete();
        }

        if (!DB::table('contract_templates')->where('contract_type_id', $typeId)->exists()
            && !DB::table('contracts')->where('contract_type_id', $typeId)->exists()) {
            DB::table('contract_types')->where('id', $typeId)->delete();
        }
    }
}