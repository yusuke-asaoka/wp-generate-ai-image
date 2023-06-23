(function ($) {
  var api_key = GenerateAiImageSettings.api_key;
  var size = GenerateAiImageSettings.image_size
    ? GenerateAiImageSettings.image_size
    : "256x256";
  var api_url = "https://api.openai.com/v1/images/generations";

  function get_image(prompt = "a white siamese cat") {
    $("#loading").show();
    fetch(api_url, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Authorization: "Bearer " + api_key,
      },
      body: JSON.stringify({
        prompt,
        n: 1,
        size,
        response_format: "b64_json",
      }),
    })
      .then((response) => {
        $("#loading").hide();
        if (!response.ok) {
          return response.json().then((errorData) => {
            throw new Error(errorData.error.message);
          });
        }
        return response.json();
      })
      .then((data) => {
        var imageBase64 = data.data[0].b64_json;
        var image_src =
          "<img src='data:image/png;base64," + imageBase64 + "'/>";
        $("#preview").html(image_src);

        $("#generate_ai_image_btns").show();
        $("#image_src").val(imageBase64);
      })
      .catch((error) => {
        console.error("Error:", error);
        $("#error_msg").text(error);
      });
  }

  function readyEvents(){
    var message = $("#generate_ai_image_text").val();
    
    if(message.length < 1) {
      alert("Please enter text");
      return
    }
    if (api_key.length < 1) {
      alert("Please enter your OpenAI API key");
    } else {
      get_image(message);
    }
  }

  $("*[name='generate_ai_image_submit']").on("click", function () {
    readyEvents()
  });
  $('#generate_ai_image_text').keydown(function(event) {
    if (event.which == 13) {
      readyEvents()
    }
  })
})(jQuery);
