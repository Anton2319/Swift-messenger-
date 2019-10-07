        function getdblenght() {
               $.ajax({
                  url: "getmessages.php",
                  method: "POST",
                  data: {
                    query:'getdblenght'
                  },
                  success: function( result ) {
                    $(".incoming").html( "<p>" + result + "</p>" );
                    //Функция уже отдебажена, не суйте руки, суки
                    //alert(result);
                    console.log("[GHOSTBUSTERS] lenght is " + result);
                    return result;
                  }
                });
        }
        function getfromdb() {
                $.ajax({
                url: "getmessages.php",
                method: "POST",
                async: false
                data: {
                    query:'getfromdb '+ getdblenght()
                },
                  success: function( result ) {
                    console.log("[GHOSTBUSTERS] result is " + result);
                    return result;
                  }
                });
        }       
                var finishid = getdblenght();
                var id = 1;
                alert(getfromdb());