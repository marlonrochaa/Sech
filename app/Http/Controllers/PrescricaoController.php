<?php

namespace App\Http\Controllers;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use DateTime;
use Auth;
use App\Prescricao;
use App\PrescricaoMedicamento;
use App\User;
use App\RelatorioAntimicrobiano;
use DB;
use App\Medicamento;
use App\Formafarmaceutica;
use App\Substanciaativa;
use App\Medicamentosubstancia;        
class PrescricaoController extends Controller {

    public function index(Request $request) {
        $idusuario = Auth::user()->id;
        $prescricoesNatendidas = Prescricao::where('prescricaos.status', 0)
                ->orderBy('id', 'DESC')
                ->where('prescricaos.idusuario', $idusuario)
                ->get();
        $prescricoesAtendidas = Prescricao::where('prescricaos.status', 1)
                ->orderBy('id', 'DESC')
                ->where('prescricaos.idusuario', $idusuario)
                ->get();
        //dd($prescricoesNatendidas[0]->medicamentos[0]->medicamento);
        return view('prescricao.index', compact('prescricoesNatendidas', 'prescricoesAtendidas'))
                        ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function buscaMedicamentos($id){
        $prescricao = Prescricao::find($id);
 
        $medicamentos = PrescricaoMedicamento::where('idprescricao', $prescricao->id)
                ->leftjoin('medicamentos', 'medicamentos.id', '=', 'prescricao_medicamentos.idmedicamento')
                ->leftjoin('relatorio_antimicrobianos', 'relatorio_antimicrobianos.idprescricao_medicamento', '=', 'prescricao_medicamentos.id')
                ->leftjoin('medicamentosubstancias' ,'medicamentosubstancias.idmedicamento','=','medicamentos.id')
                ->leftjoin('substanciaativas' ,'substanciaativas.id','=','medicamentosubstancias.idsubstanciaativa')
                ->leftjoin('formafarmaceuticas' ,'formafarmaceuticas.id','=','medicamentos.idformafarmaceutica')
                //->where('medicamentos.id', '!=', null)
                ->select('substanciaativas.classificacao' ,'medicamentosubstancias.unidadedose','formafarmaceuticas.nome as nomeforma', 'medicamentos.nomeconteudo','substanciaativas.nome as nomesubstancia','medicamentosubstancias.quantidadedose','substanciaativas.codigo','prescricao_medicamentos.obs','prescricao_medicamentos.administracao','prescricao_medicamentos.estabilidade','prescricao_medicamentos.diluicao','prescricao_medicamentos.dose as dosemed','prescricao_medicamentos.outros',

                 'medicamentos.quantidadeconteudo', 'medicamentos.unidadeconteudo', 'medicamentos.codigosimpas', 'prescricao_medicamentos.id as idprescmed', 'prescricao_medicamentos.idprescricao', 'prescricao_medicamentos.idmedicamento', 'prescricao_medicamentos.qtdpedida', 'prescricao_medicamentos.qtdatendida', 'prescricao_medicamentos.posologia','relatorio_antimicrobianos.diagnostico_infeccioso', 'relatorio_antimicrobianos.id as idrelatorio'
                   ,'relatorio_antimicrobianos.nome','relatorio_antimicrobianos.leito','relatorio_antimicrobianos.data_admissao','relatorio_antimicrobianos.inicio_tratamento','relatorio_antimicrobianos.clinica','relatorio_antimicrobianos.duracao_tratamento','relatorio_antimicrobianos.antimicrobiano')
                ->get();

            if($medicamentos == '[]'){
                //$medicamentos = ['12','12'];
                $medicamentos_semSimpas = PrescricaoMedicamento::where('idprescricao', $prescricao->id)
                ->select('prescricao_medicamentos.outros as nomesubstancia', 'prescricao_medicamentos.obs','prescricao_medicamentos.administracao','prescricao_medicamentos.estabilidade','prescricao_medicamentos.diluicao','prescricao_medicamentos.dose as dosemed','prescricao_medicamentos.id as idprescmed', 'prescricao_medicamentos.idprescricao', 'prescricao_medicamentos.idmedicamento', 'prescricao_medicamentos.qtdpedida', 'prescricao_medicamentos.qtdatendida', 'prescricao_medicamentos.posologia')
                ->get();
            }

            //$result =  array_unique($medicamentos, $medicamentos_semSimpas);    

            return response()->json($medicamentos);
           
    }

    public function create() {
        $data = Medicamento::all();

        $results = array();

        foreach ($data as $medicamento) {
            $substancias = '';
            foreach ($medicamento->medicamentosubstancias as $medicamentosubstancia) {
                $nomeunidade = '';
                switch ($medicamentosubstancia->unidadedose) {
                    case 0:
                        $nomeunidade = 'mcg';
                        break;
                    case 1:
                        $nomeunidade = 'mg';
                        break;
                    case 2:
                        $nomeunidade = 'g';
                        break;
                    case 3:
                        $nomeunidade = 'UI';
                        break;
                    case 4:
                        $nomeunidade = 'unidades';
                        break;
                    case 5:
                        $nomeunidade = 'mg/g';
                        break;
                    case 6:
                        $nomeunidade = 'UI/g';
                        break;
                    case 7:
                        $nomeunidade = 'mEq/mL';
                        break;
                    case 8:
                        $nomeunidade = 'mg/gota';
                        break;
                    case 9:
                        $nomeunidade = 'mcg/mL';
                        break;
                    case 10:
                        $nomeunidade = 'UI/mL';
                        break;
                    case 11:
                        $nomeunidade = 'mEq';
                        break;
                    case 12:
                        $nomeunidade = 'mg/mL';
                        break;
                    case 13:
                        $nomeunidade = 'mL';
                        break;
                    case 14:
                        $nomeunidade = 'Seringa Pré-enchida';
                    break;
                    case 15:
                        $nomeunidade = 'Kcal/L';
                    break;    
                    default:
                        $nomeunidade = '';       
                    break;        
                }
                $substancias .= $medicamentosubstancia->substanciaativa->nome.' '. $medicamentosubstancia->quantidadedose. ' '. $nomeunidade . ', ';
                 $class = $medicamentosubstancia->substanciaativa->classificacao;
            }
            $substancias .= $medicamento->formafarmaceuticas->nome . ' ';
            $conteudo = '';
            switch ($medicamento->nomeconteudo) {
                case 0:
                    $conteudo = 'Frasco';
                    break;
                case 1:
                    $conteudo = 'FA (frasco ampola)';
                    break;
                case 2:
                    $conteudo = 'AMP (ampola)';
                    break;
                case 3:
                    $conteudo = 'Caixa';
                    break;
                case 4:
                    $conteudo = 'Envelope';
                    break;
                case 5:
                    $conteudo = 'Tubo';
                    break;
                case 6:
                    $conteudo = 'Bolsa';
                    break;
                case 7:
                    $conteudo = 'Pote';
                    break;
            }
            
            switch ($medicamento->unidadeconteudo) {
                case 0:
                    $uc = 'mcg';
                    break;
                case 1:
                    $uc = 'mg';
                    break;
                case 2:
                    $uc = 'g';
                    break;
                case 3:
                    $uc = 'UI';
                    break;
                case 4:
                    $uc = 'unidades';
                    break;
                case 5:
                    $uc = 'mg/g';
                    break;
                case 6:
                    $uc = 'UI/g';
                    break;
                case 7:
                    $uc = 'mEq/mL';
                    break;
                case 8:
                    $uc = 'mg/gota';
                    break;
                case 9:
                    $uc = 'mcg/mL';
                    break;
                case 10:
                    $uc = 'UI/mL';
                    break;
                case 11:
                    $uc = 'mEq';
                    break;
                case 12:
                    $uc = 'mg/mL';
                    break;
                case 13:
                    $uc = 'mL';
                    break;      
                default:
                    $uc = '';       
                    break;
            }
            $substancias .= '' . $conteudo . ' com ' . $medicamento->quantidadeconteudo . ' '. $uc;
            //$substancias .= '' . $conteudo;
            $results[] = [
                
                'codigo' => $medicamentosubstancia->substanciaativa->codigo,
                'diluicao' =>$medicamentosubstancia->substanciaativa->diluicao,
                'dose' => $medicamentosubstancia->substanciaativa->dose,
                'administracao' => $medicamentosubstancia->substanciaativa->administracao,
                'estabilidade' => $medicamentosubstancia->substanciaativa->estabilidade,
                'id' => $medicamento->id,
                'value' => $substancias, 'classificacao' => $class
            ];
        }


        $dataprescricao = date("d/m/Y H:i:s");
        $id = Auth::user()->id;
        $medico = User::find($id)->name;
        return view('prescricao.create', compact('dataprescricao', 'medico', 'results'));
    }


    public function editar($id) {
        $data = Medicamento::all();

        $results = array();

        foreach ($data as $medicamento) {
            $substancias = '';
            foreach ($medicamento->medicamentosubstancias as $medicamentosubstancia) {
                $nomeunidade = '';
                switch ($medicamentosubstancia->unidadedose) {
                    case 0:
                        $nomeunidade = 'mcg';
                        break;
                    case 1:
                        $nomeunidade = 'mg';
                        break;
                    case 2:
                        $nomeunidade = 'g';
                        break;
                    case 3:
                        $nomeunidade = 'UI';
                        break;
                    case 4:
                        $nomeunidade = 'unidades';
                        break;
                    case 5:
                        $nomeunidade = 'mg/g';
                        break;
                    case 6:
                        $nomeunidade = 'UI/g';
                        break;
                    case 7:
                        $nomeunidade = 'mEq/mL';
                        break;
                    case 8:
                        $nomeunidade = 'mg/gota';
                        break;
                    case 9:
                        $nomeunidade = 'mcg/mL';
                        break;
                    case 10:
                        $nomeunidade = 'UI/mL';
                        break;
                    case 11:
                        $nomeunidade = 'mEq';
                        break;
                    case 12:
                        $nomeunidade = 'mg/mL';
                        break;
                    case 13:
                        $nomeunidade = 'mL';
                        break;
                    case 14:
                        $nomeunidade = 'Seringa Pré-enchida';
                    break;
                    case 15:
                        $nomeunidade = 'Kcal/L';
                    break;    
                    default:
                        $nomeunidade = '';       
                    break;        
                }
                $substancias .= $medicamentosubstancia->substanciaativa->nome.' '. $medicamentosubstancia->quantidadedose. ' '. $nomeunidade . ', ';
                 $class = $medicamentosubstancia->substanciaativa->classificacao;
            }
            $substancias .= $medicamento->formafarmaceuticas->nome . ' ';
            $conteudo = '';
            switch ($medicamento->nomeconteudo) {
                case 0:
                    $conteudo = 'Frasco';
                    break;
                case 1:
                    $conteudo = 'FA (frasco ampola)';
                    break;
                case 2:
                    $conteudo = 'AMP (ampola)';
                    break;
                case 3:
                    $conteudo = 'Caixa';
                    break;
                case 4:
                    $conteudo = 'Envelope';
                    break;
                case 5:
                    $conteudo = 'Tubo';
                    break;
                case 6:
                    $conteudo = 'Bolsa';
                    break;
                case 7:
                    $conteudo = 'Pote';
                    break;
            }
            
            switch ($medicamento->unidadeconteudo) {
                case 0:
                    $uc = 'mcg';
                    break;
                case 1:
                    $uc = 'mg';
                    break;
                case 2:
                    $uc = 'g';
                    break;
                case 3:
                    $uc = 'UI';
                    break;
                case 4:
                    $uc = 'unidades';
                    break;
                case 5:
                    $uc = 'mg/g';
                    break;
                case 6:
                    $uc = 'UI/g';
                    break;
                case 7:
                    $uc = 'mEq/mL';
                    break;
                case 8:
                    $uc = 'mg/gota';
                    break;
                case 9:
                    $uc = 'mcg/mL';
                    break;
                case 10:
                    $uc = 'UI/mL';
                    break;
                case 11:
                    $uc = 'mEq';
                    break;
                case 12:
                    $uc = 'mg/mL';
                    break;
                case 13:
                    $uc = 'mL';
                    break;      
                default:
                    $uc = '';       
                    break;
            }
            $substancias .= '' . $conteudo . ' com ' . $medicamento->quantidadeconteudo . ' '. $uc;
            //$substancias .= '' . $conteudo;
            $results[] = [
                
                'codigo' => $medicamentosubstancia->substanciaativa->codigo,
                'diluicao' =>$medicamentosubstancia->substanciaativa->diluicao,
                'dose' => $medicamentosubstancia->substanciaativa->dose,
                'administracao' => $medicamentosubstancia->substanciaativa->administracao,
                'estabilidade' => $medicamentosubstancia->substanciaativa->estabilidade,
                'id' => $medicamento->id,
                'value' => $substancias, 'classificacao' => $class
            ];
        }

        $prescricao = Prescricao::find($id);

        $idprescricao = $id;
        
        $dataprescricao = date("d/m/Y H:i:s");
        $id = Auth::user()->id;
        $medico = User::find($id)->name;

        $medicamentos = PrescricaoMedicamento::where('idprescricao', $prescricao->id)
                ->join('medicamentos', 'medicamentos.id', '=', 'prescricao_medicamentos.idmedicamento')
                ->leftjoin('relatorio_antimicrobianos', 'relatorio_antimicrobianos.idprescricao_medicamento', '=', 'prescricao_medicamentos.id')
                ->where('idmedicamento', '!=', null)
                ->select('medicamentos.id', 'medicamentos.idformafarmaceutica', 'medicamentos.nomeconteudo', 'medicamentos.quantidadeconteudo', 'medicamentos.unidadeconteudo', 'medicamentos.codigosimpas', 'prescricao_medicamentos.id as idprescmed', 'prescricao_medicamentos.idprescricao', 'prescricao_medicamentos.idmedicamento', 'prescricao_medicamentos.qtdpedida', 'prescricao_medicamentos.qtdatendida', 'prescricao_medicamentos.posologia','relatorio_antimicrobianos.diagnostico_infeccioso', 'relatorio_antimicrobianos.id as idrelatorio'
                   ,'relatorio_antimicrobianos.nome','relatorio_antimicrobianos.leito','relatorio_antimicrobianos.data_admissao','relatorio_antimicrobianos.inicio_tratamento','relatorio_antimicrobianos.clinica','relatorio_antimicrobianos.duracao_tratamento','relatorio_antimicrobianos.antimicrobiano')
                //->where('prescricao_medicamentos.qtdatendida', 0)
                ->get();
         
        return view('prescricao.editar', compact('prescricao.create','prescricao', 'medicamentos','dataprescricao', 'medico','idprescricao', 'results'));

    }

    public function store(Request $request) {
        $prescricao = new Prescricao();
        $prescricao->idusuario = Auth::user()->id;
        $prescricao->idinternacao = $request->get('idinternacao');
        $prescricao->dataprescricao = $request->get('dataprescricao');
        $prescricao->evolucao = $request->get('evolucao');
        $prescricao->observacoesmedicas = $request->get('observacoesmedicas');
        $medicamentos = $request->get('prescricaomedicamento');
        $relatorio = $request->get('relatorioAntimicro');


        if($request->get('idprescricaopai')){
            $id = $request->get('idprescricaopai');
            
            $busca_pai = Prescricao::find($id);
            $id_pai_maior = 0 ;

            if($busca_pai->id_pai != null){// se for null ele pega o id da primeira prescrição
                $prescricao->id_pai = $busca_pai->id_pai;
                $id_pai_maior = $busca_pai->id_pai; 

            }else{//pega o id da segunda prescrição que já tem outro pai
                $prescricao->id_pai = $id;
                $id_pai_maior = $id;
            }


            $busca_pai = PrescricaoMedicamento::where('idprescricao', $id_pai_maior)
                ->join('medicamentos', 'medicamentos.id', '=', 'prescricao_medicamentos.idmedicamento')
                ->leftjoin('relatorio_antimicrobianos', 'relatorio_antimicrobianos.idprescricao_medicamento', '=', 'prescricao_medicamentos.id')
                ->where('idmedicamento', '!=', null)
                ->select('relatorio_antimicrobianos.inicio_tratamento', 'relatorio_antimicrobianos.antimicrobiano','relatorio_antimicrobianos.quantidade','relatorio_antimicrobianos.duracao_tratamento')
                ->get();
            
            $data_atual = date("Y/m/d 23:59:59");

            $vet ='';
            $x = 0;
            $verifica = false;
            $dias = 0;
            for ($i = 0; $i < sizeof($busca_pai); $i++) {               
                $dd = $busca_pai[$i]->inicio_tratamento;
                $dd = $dd." 23:59:59";
                
                if($busca_pai[$i]->duracao_tratamento == "Dia(s)"){
                    $dias = $busca_pai[$i]->quantidade;
                }else if($busca_pai[$i]->duracao_tratamento == "Semana(s)"){
                    $dias = $busca_pai[$i]->quantidade * 7;
                }else if($busca_pai[$i]->duracao_tratamento == "Mês(es)"){
                    $dias = $busca_pai[$i]->quantidade * 30;
                } 

                $data = date('Y-m-d H:i:s', strtotime("+".$dias."days",strtotime($dd)));

                $cont = $i;
                if($relatorio[$i]['medInfe'] != ''){
                    if( strtotime($data) >= strtotime($data_atual) ){
                        $vet .= ' '.$cont.' - '.$busca_pai[$i]->antimicrobiano; // pega os relatorios pra informar quais estao vencidos
                        $verifica = true;
                    }
                }
            }
          
            if($verifica){ //compara se a data atual é maior que a primeira prescriação, para emitir um novo relatório antimi 
                
            return response::create($vet,202);
            
            }else{
                 $prescricao->id_pai = null;
            }
        }

        $prescricao->save();
        $idprescricao = $prescricao->id;         
        
        $j = 0;
        for ($i = 0; $i < sizeof($medicamentos); $i++) {
            $prescricaomedicamento = new PrescricaoMedicamento();
            $prescricaomedicamento->idprescricao = $idprescricao;

            if ($medicamentos[$i]['idmedicamento'] == '') {
                $prescricaomedicamento->qtdpedida = (isset($medicamentos[$i]['qtd'])) ? 0 : $medicamentos[$i]['qtd'];
                $prescricaomedicamento->qtdatendida = 0;
                $prescricaomedicamento->posologia = $medicamentos[$i]['posologia'];
                $prescricaomedicamento->outros = $medicamentos[$i]['med'];
                $prescricaomedicamento->obs = (!isset($medicamentos[$i]['obs'])) ? '' : $medicamentos[$i]['obs'];
                $prescricaomedicamento->dose = (!isset($medicamentos[$i]['dose'])) ? '' : $medicamentos[$i]['dose'];
                $prescricaomedicamento->diluicao = (!isset($medicamentos[$i]['diluicao'])) ? '' : $medicamentos[$i]['diluicao'];
                $prescricaomedicamento->administracao = (!isset($medicamentos[$i]['administracao'])) ? '' : $medicamentos[$i]['administracao'];
                $prescricaomedicamento->estabilidade = (!isset($medicamentos[$i]['estabilidade'])) ? '' : $medicamentos[$i]['estabilidade'];
                $prescricaomedicamento->simpas = '-';

                $prescricaomedicamento->save();
            } else {
                //$prescricaomedicamento->idprescricao = $idprescricao;              
                $prescricaomedicamento->qtdatendida = 0;
                $prescricaomedicamento->outros = '';
                $prescricaomedicamento->idmedicamento = $medicamentos[$i]['idmedicamento'];
                $prescricaomedicamento->qtdpedida =  (!isset($medicamentos[$i]['qtd'])) ? 0 : $medicamentos[$i]['qtd'];
                $prescricaomedicamento->posologia = $medicamentos[$i]['posologia'];
                $prescricaomedicamento->obs = (!isset($medicamentos[$i]['obs'])) ? '' : $medicamentos[$i]['obs'];
                $prescricaomedicamento->dose = (!isset($medicamentos[$i]['dose'])) ? '' : $medicamentos[$i]['dose'];
                $prescricaomedicamento->diluicao = (!isset($medicamentos[$i]['diluicao'])) ? '' : $medicamentos[$i]['diluicao'];
                $prescricaomedicamento->administracao = (!isset($medicamentos[$i]['administracao'])) ? '' : $medicamentos[$i]['administracao'];
                $prescricaomedicamento->estabilidade = (!isset($medicamentos[$i]['estabilidade'])) ? '' : $medicamentos[$i]['estabilidade'];
                $prescricaomedicamento->simpas = $medicamentos[$i]['simpas'];

                $prescricaomedicamento->save();

                if($relatorio[$i]['medInfe'] != '' && $relatorio[$i]['iniTrata'] != ''){
                    $RelatorioAntimicrobiano = new RelatorioAntimicrobiano();
                    $RelatorioAntimicrobiano->idprescricao_medicamento = $prescricaomedicamento->id;
                    $RelatorioAntimicrobiano->nome = $relatorio[$i]['paciente'];
                    $RelatorioAntimicrobiano->leito = $relatorio[$i]['leito'];
                    $RelatorioAntimicrobiano->data_admissao = $relatorio[$i]['dataadmissao'];
                    $RelatorioAntimicrobiano->inicio_tratamento = $relatorio[$i]['iniTrata'];
                    $RelatorioAntimicrobiano->clinica = $relatorio[$i]['clinica'];
                    $RelatorioAntimicrobiano->diagnostico_infeccioso = $relatorio[$i]['diagInfe'];
                    $RelatorioAntimicrobiano->duracao_tratamento = $relatorio[$i]['duracao'];
                    $RelatorioAntimicrobiano->antimicrobiano = $relatorio[$i]['medInfe'];
                    $RelatorioAntimicrobiano->quantidade = $relatorio[$i]['quantidade'];
                    
                    $RelatorioAntimicrobiano->save();
                }
            }
            
        }

        

        return redirect()->route('internacao.index')
                        ->with('success', 'Paciente internado com sucesso!');

    }

    public function edit($id) {
        $prescricao = Prescricao::find($id);
        
        

        $medicamentos = PrescricaoMedicamento::where('idprescricao', $prescricao->id)
                ->leftjoin('medicamentos', 'medicamentos.id', '=', 'prescricao_medicamentos.idmedicamento')
                ->leftjoin('relatorio_antimicrobianos', 'relatorio_antimicrobianos.idprescricao_medicamento', '=', 'prescricao_medicamentos.id')
                //->where('idmedicamento', '!=', null)
                ->select('medicamentos.id', 'medicamentos.idformafarmaceutica', 'medicamentos.nomeconteudo', 'medicamentos.quantidadeconteudo', 'medicamentos.unidadeconteudo', 'medicamentos.codigosimpas', 'prescricao_medicamentos.id as idprescmed', 'prescricao_medicamentos.idprescricao', 'prescricao_medicamentos.idmedicamento', 'prescricao_medicamentos.qtdpedida','prescricao_medicamentos.outros', 'prescricao_medicamentos.qtdatendida', 'prescricao_medicamentos.posologia','relatorio_antimicrobianos.diagnostico_infeccioso', 'relatorio_antimicrobianos.id as idrelatorio'
                   ,'relatorio_antimicrobianos.nome','relatorio_antimicrobianos.leito','relatorio_antimicrobianos.data_admissao','relatorio_antimicrobianos.inicio_tratamento','relatorio_antimicrobianos.clinica','relatorio_antimicrobianos.duracao_tratamento','relatorio_antimicrobianos.antimicrobiano','relatorio_antimicrobianos.quantidade')
                //->where('prescricao_medicamentos.qtdatendida', 0)
                ->get();
                
        if($medicamentos == '[]'){
            $medicamentos = PrescricaoMedicamento::where('idprescricao', $prescricao->id)->get();               
        }


        return view('prescricao.edit', compact('prescricao', 'medicamentos'));
    }

    public function update(Request $request, $id) {
        //dd($id) and die();
//        $this->validate($request, [
//            'nome' => 'required',
//            'descricao' => 'required',
//        ]);
        
        $prescricao = Prescricao::find($id);
        $prescricao->status = 1;
        $prescricao->save();

        return redirect()->route('prescricao.index')
                        ->with('success', 'Prescrição resolvida com sucesso!');
    }


}
