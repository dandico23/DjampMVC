$(document).ready(function () {
  checkRequired();
  handleSTEspecial();
  handleNaoPossuiResponsavel();
});

function handleSTEspecial () {
  $('#divbo').hide();
  $('#st_especial').click(function() {
    if ($(this).is(':checked')) {
      $('#divbo').fadeIn(300);
    } else{
      $('#divbo').fadeOut(200);
    }
  });
}


// Falta excluir as regras de validação quando st_nao_possui_responsavel estiver Checked.
// Por enquanto não consegui porque o jquery-validation é bem chato

function checkRequired () {
  // if ($('#st_nao_possui_responsavel').is(':checked')) {
      $('#acolhidoForm').validate({
        // debug: true,
        rules: {
          nome: {
            required: true,
            minlength: 4,
            maxlength: 40
          },
          data_nascimento: {
            required: true,
            minlength: 10
          },
          cpf: {
            required: true,
            minlength: 11,
          },
          tp_sexo: {
            required: true,
          },
          nm_responsavel: {
            required: true,
            minlength: 4,
            maxlength: 40
          },
          cpf_responsavel: {
            required: true,
            minlength: 11
          },
          tp_sexo_responsavel: {
            required: true,
          },
          id_grau_parentesco: {
            required: true,
          }
        },
        messages: {
          nome: "Insira um nome válido.",
          data_nascimento: "Insira uma data válida.",
          cpf: "Insira um CPF válido.",
          tp_sexo: "Selecione uma opção.",
          nm_responsavel: "Insira um nome válido.",
          cpf_responsavel: "Insira um CPF válido.",
          tp_sexo_responsavel: "Selecione uma opção.",
          id_grau_parentesco: "Selecione uma opção."
        },
        errorPlacement: function (label, element) {
          label.addClass('errorStyle');
          label.insertAfter(element);
        },
        wrapper:'span'
      });

    // else {
    //   $('#acolhidoForm').validate({
    //     debug: true,
    //     rules: {
    //       nome: {
    //         required: true,
    //         minlength: 4,
    //         maxlength: 40
    //       },
    //       data_nascimento: {
    //         required: true,
    //         minlength: 10
    //       },
    //       cpf: {
    //         required: true,
    //         minlength: 11,
    //       },
    //       tp_sexo: {
    //         required: true,
    //       }
    //     },
    //     messages: {
    //       nome: "Insira um nome válido.",
    //       data_nascimento: "Insira uma data válida.",
    //       cpf: "Insira um CPF válido.",
    //       tp_sexo: "Selecione uma opção."
    //     },
    //     errorPlacement: function (label, element) {
    //       label.addClass('errorStyle');
    //       label.insertAfter(element);
    //     },
    //     wrapper:'span'
    //   });
    // }
}

function handleNaoPossuiResponsavel () {
  $('#st_nao_possui_responsavel').click(function() {
    checkRequired();
    if ($(this).is(':checked')) {
      $('.inputResponsavel').prop('disabled', true);
      clearResponsavelForm();
    } else{
      $('.inputResponsavel').prop('disabled', false);
    }
  });
}

function clearResponsavelForm () {
  $('.inputResponsavel').val('');
}

function removeRules(rulesObj){
  for (var item in rulesObj) {
     $('input[name="'+item+'"]').rules('remove');  
  } 
}