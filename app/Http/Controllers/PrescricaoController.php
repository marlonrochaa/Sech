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
        $dataprescricao = date("d/m/Y H:i:s");
        $id = Auth::user()->id;
        $medico = User::find($id)->name;
        return view('prescricao.create', compact('dataprescricao', 'medico'));
    }


    public function editar($id) {
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
         
        return view('prescricao.editar', compact('prescricao.create','prescricao', 'medicamentos','dataprescricao', 'medico','idprescricao'));

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
            $prescricao->id_pai = $id;

            $busca_pai = Prescricao::find($id);
            $data  = $busca_pai->created_at;
            $data_atual = date("Y/m/d 23:59:59");

            $data1 = new DateTime($data);
            $data2 = new DateTime($data_atual);

            $x = $data1->diff($data2);
            
            if($x->days > 0){ //compara se a data atual é maior que(8) a primeira prescriação para emitir um novo relatório antimi 
                $medic = PrescricaoMedicamento::where('idprescricao', $busca_pai->id)
                ->leftjoin('medicamentos', 'medicamentos.id', '=', 'prescricao_medicamentos.idmedicamento')
                ->leftjoin('medicamentosubstancias' ,'medicamentosubstancias.idmedicamento','=','medicamentos.id')
                ->leftjoin('substanciaativas' ,'substanciaativas.id','=','medicamentosubstancias.idsubstanciaativa')
                ->select('medicamentos.id','substanciaativas.nome as nomesubstancia')
                ->get();


                for ($i = 0; $i < sizeof($medicamentos); $i++) {
                    if($medicamentos[$i]['classificacao'] == 2 && $relatorio[$i]['medInfe'] == ''){
                        if($medic[$i]->id == $medicamentos[$i]['idmedicamento']){
                            $response = new Response();
                            return $response->setStatusCode(202, $medic[$i]['nomesubstancia']);
                        }
                    }
                }
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

                if($medicamentos[$i]['classificacao'] == 2 && $relatorio[$i]['medInfe'] != ''){
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
                   ,'relatorio_antimicrobianos.nome','relatorio_antimicrobianos.leito','relatorio_antimicrobianos.data_admissao','relatorio_antimicrobianos.inicio_tratamento','relatorio_antimicrobianos.clinica','relatorio_antimicrobianos.duracao_tratamento','relatorio_antimicrobianos.antimicrobiano')
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
