function formataCep(field, teclapres){
	var tecla = teclapres.keyCode;
	var vr = new String(field.value);
	vr = vr.replace("-", "");
	tam = vr.length + 1;
	if (tecla != 8){
		if (tam == 6)
			field.value = vr.substr(0, 5) + '-' + vr.substr(5, 5);
	}
}

function exibeLoading(){
	var tamanhoCep = document.getElementById("cepDestino").value;
	tamanhoCep = tamanhoCep.length;

	if (tamanhoCep >= 9){
		document.getElementById("divForm").style.display = 'none';
		document.getElementById("loadingFrame").style.display = 'block';
		return true;
	}
	else {
		alert("Preencha o CEP corretamente");
		return false;
	}
}