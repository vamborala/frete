<?php 
// Função que busca os valores enviados pelo formulário, tanto por POST como GET
function getval($val, $default){
	// retorna um valor de POST ou GET ou o valor default caso não exista o índice
	if (array_key_exists($val, $_POST))	return $_POST[$val];
	if (array_key_exists($val, $_GET))	return $_GET[$val];
    return $default;
}

if (getval('btnSubmit', false)){

	error_reporting('E_ERROR');

	// Cep de destino original
	$sCepDestinoOriginal = getval('cepDestino', "");

	// Código da empresa junto aos Correios
	$nCdEmpresa = "12027162";
	// Senha da empresa junto aos Correios
	$sDsSenha = "12056936";
	// Código dos serviços a serem pesquisados
	$nCdServicoOpcoes = array(	
								//"41106" => array("nome" => "PAC", "image" => "pac.gif"), // SEM contrato
								"41068" => array("nome" => "PAC", "image" => "pac.gif"), // COM contrato
								//"40010" => array("nome" => "Sedex", "image" => "sedex.gif"), // SEM contrato
								"40096" => array("nome" => "Sedex", "image" => "sedex.gif"), // COM contrato
								"40215" => array("nome" => "Sedex10", "image" => "sedex10.gif"), // Sedex10 só permite COM contrato
								"81019" => array("nome" => "eSedex", "image" => "esedex.gif") // e-Sedex só permite COM contrato 
								);
	$nCdServico = implode(",", array_keys($nCdServicoOpcoes));
	// Cep de origem
	$sCepOrigem = 3310000;
	// Cep de destino
	$sCepDestino = preg_replace("/[^0-9]/", "", $sCepDestinoOriginal);

	// PARÂMETROS CONFIGURÁVEIS ATRAVÉS DA URL
	// Peso da mercadoria (incluindo embalagem)
	$nVlPeso = getval('p', 1);
	// Formato da embalagem
	$nCdFormato = getval('f', 1);
	// Comprimento da encomenda incluindo embalagem (em centímetros)
	$nVlComprimento = getval('c', 20);
	// Altura da encomenda incluindo embalagem (em centímetros)
	$nVlAltura = getval('a', 5);
	// Largura da encomenda incluindo embalagem (em centímetros)
	$nVlLargura = getval('l', 15);
	// Diametro da encomenda incluindo embalagem (em centímetros)
	$nVlDiametro = getval('d', 0);
	// Indica se a encomenda será entregue com o serviço adicional mão própria
	$sCdMaoPropria = getval('mp', "N");
	// Indica se a encomenda será entregue com o serviço adicional valor declarado.
	$nVlValorDeclarado = getval('vd', 0);
	// Indica se a encomenda será entregue com o serviço adicional aviso de recebimento
	$sCdAvisoRecebimento = getval('ar', "N");
	// Valor da embalagem a ser inserido no valor final do frete
	$nVlrEmbalagem = getval('vem', 0);
	// Flag para indicar se a Carta Registrada será grátis
	$fCrGratis = getval('crg', false);
	// Flag para indicar se o PAC será grátis
	$fPacGratis = getval('pag', false);
	// Flag para indicar se o e-Sedex estará disponível
	$fESedexDesabilitado = getval('esd', false);

	$sGratis = "ENVIO GRÁTIS";
	$sNaoDisponivel = "Não disponível";

	// Valor da carta registrada
	$cartaRegistrada = getval('cr', 0);
	$diasCr = getval('dcr', 0);
	if ($fCrGratis && intval($diasCr) != 0){
		$cartaRegistrada = $sGratis;
		$textoCr  = "Até $diasCr";
		$textoCr .= ($diasCr == 1) ? " dia útil" : " dias úteis";
	}
	else if (intval($cartaRegistrada) != 0 && intval($diasCr) != 0){
		$cartaRegistrada = "R$ " . number_format($cartaRegistrada, 2, ",", ".");
		$textoCr  = "Até $diasCr";
		$textoCr .= ($diasCr == 1) ? " dia útil" : " dias úteis";
	}
	else {
		$cartaRegistrada = "-";
		$textoCr = $sNaoDisponivel;
	}

	// URL de pesquisa nos correios
	$sURL  = "http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx?";
	$sURL .= "nCdEmpresa=$nCdEmpresa&";
	$sURL .= "sDsSenha=$sDsSenha&";
	$sURL .= "nCdServico=$nCdServico&";
	$sURL .= "sCepOrigem=$sCepOrigem&";
	$sURL .= "sCepDestino=$sCepDestino&";
	$sURL .= "nVlPeso=$nVlPeso&";
	$sURL .= "nCdFormato=$nCdFormato&";
	$sURL .= "nVlComprimento=$nVlComprimento&";
	$sURL .= "nVlAltura=$nVlAltura&";
	$sURL .= "nVlLargura=$nVlLargura&";
	$sURL .= "nVlDiametro=$nVlDiametro&";
	$sURL .= "sCdMaoPropria=$sCdMaoPropria&";
	$sURL .= "nVlValorDeclarado=$nVlValorDeclarado&";
	$sURL .= "sCdAvisoRecebimento=$sCdAvisoRecebimento&";
	$sURL .= "StrRetorno=xml";

	// Busca das informações de frete
	$dados = simplexml_load_file($sURL);

	// Busca das informações sobre o endereço (cidade e UF), através de cURL no site mobile dos correios
	// Setando as variáveis POST
	$url = "http://m.correios.com.br/movel/buscaCepConfirma.do";
	$fields = array(
				'cepEntrada'=>$sCepDestino,
				'tipoCep'=>'',
				'cepTemp'=>'',
				'metodo'=>'buscarCep'
	        );

	// Abrindo a conexão
	$ch = curl_init();

	// Setando a URL, a quantidade de variáveis POST e os valores das mesmas
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_POST, count($fields));
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
	curl_setopt($ch, CURLOPT_FORBID_REUSE, TRUE);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

	// Executando o POST
	$result = curl_exec($ch);

	// Transformando o valor de retorno em um objeto DOM
	$dom = new DOMDocument;
	$dom->loadHTML($result);

	// loop para identificar em qual índice estão as infos de cidade/uf
	for ($i=0;$i<=8;$i++){
		if (strpos($dom->getElementsByTagName('span')->item($i)->nodeValue, "/") !== false) $indexCidade = $i;
	}

	// Recuperando no índice $indexCidade os valores de cidade/uf
	$spanEndereco = explode ("/", $dom->getElementsByTagName('span')->item($indexCidade)->nodeValue);

	// Atribuindo os valores às variáveis
	$dadosEndereco['cep'] = $sCepDestinoOriginal;
	$dadosEndereco['cidade'] = trim($spanEndereco[0]);
	$dadosEndereco['uf'] = trim($spanEndereco[1]);

	// Fechando a conexão
	curl_close($ch);
	?>

	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>Cálculo de Frete Marcelo Dellatorre</title>
	<link rel="stylesheet" type="text/css" href="style.css" />
	</head>
	<body>
	<div class="banner">
		<img src="logo_continental.png" width="600px">
	</div>
	<br />
	<table align="center">
		<tr id="linhaInfos">
			<td id="cep">CEP de destino: <span class="cepSpan"><?php echo $dadosEndereco['cep'] ?></span></td>
			<td id="cidade">Cidade: <span class="cepSpan"><?php echo $dadosEndereco['cidade'] ?></span></td>
			<td id="uf">UF: <span class="cepSpan"><?php echo $dadosEndereco['uf'] ?></span></td>
		</tr>
	</table>
	<table align="center">
		<tr class="linhaServico">
			<td class="celImageHeader">&nbsp;</td>
			<td class="celValorHeader">Valor do Frete</td>
			<td class="celPrazoHeader">Prazo de Entrega</td>
		</tr>

		<tr>
			<td class="celImage"><img src="cartareg.gif" /></td>
			<td class="celValor"><?php echo $cartaRegistrada ?> </td>
			<td class="celPrazo"><?php echo $textoCr ?></td>
		</tr>

		<?php
		if (!empty($dados)){
			foreach($dados as $value){
				$dadosServico = get_object_vars($value);
				if (empty($dadosServico["Erro"])){
					$valorServico = str_replace(",", ".", $dadosServico["Valor"]);
					if ($nVlrEmbalagem != 0)
						$valorServico = $valorServico + $nVlrEmbalagem;	
	
					$valorServico = number_format($valorServico, 2, ",", ".");
					$valorServico = "R$ " . $valorServico;
					$prazoEntrega = $dadosServico["PrazoEntrega"];
					$prazoEntrega .= ($dadosServico["PrazoEntrega"] == 1) ? " dia útil" : " dias úteis";
	
					if ($dadosServico["Codigo"] == '41068' && $fPacGratis) // Filtragem para gratuidade do PAC
						$valorServico = $sGratis;
					if ($dadosServico["Codigo"] == '81019' && $fESedexDesabilitado){ // Filtragem caso o e-Sedex esteja desabilitado
						$valorServico = "-";
						$prazoEntrega = $sNaoDisponivel;
					}
					?>
					<tr>
						<td class="celImage"><img src="<?php echo $nCdServicoOpcoes[$dadosServico["Codigo"]]["image"] ?>" /></td>
						<td class="celValor"><?php echo $valorServico ?></td>
						<td class="celPrazo"><?php echo $prazoEntrega ?></td>
					</tr>
				<?php
				} else { ?>
					<tr>
						<td class="celImage"><img src="<?php echo $nCdServicoOpcoes[$dadosServico["Codigo"]]["image"] ?>" /></td>
						<td class="msgErro" colspan="2"><?php echo $dadosServico["MsgErro"]?></td>
					</tr>
				<?php 
				}
			}
		 }?>
	</table>
	</body>
	</html>

	<?php
	exit;
}
?>

<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Sistema de cálculo de frete by dellatorre.co</title>
<link rel="stylesheet" type="text/css" href="style.css" />
<script type="text/javascript" src="functions.js"></script>
</head>
<body>
	<div class="banner">
		<img src="logo_continental.png" width="600px">
	</div>
	<div class="banner" id="loadingFrame" style="display: none;">
		<img src="aguarde.gif" style="margin-top: -85px;" />
	</div>
	<div id="divForm">
		<form name='consulta' id='consulta' method="post" onSubmit="return exibeLoading()">
			<table align="center" id="formulario">
				<tr>
					<td class="labelCep">Digite seu CEP:<br />
					<input type="text" name="cepDestino" id="cepDestino" size="7" maxlength="9" onkeyup="formataCep(this, event)" /></td>
					<td class="labelSubmit"><input type="submit" name="btnSubmit" id="btnSubmit" value="Calcular Frete" /></td>
				</tr>
			</table>
		</form>
	</div>
</body>
</html>
