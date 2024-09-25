<?php
include '../../config.php';
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_name);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Function to get a setting value
function getSettingValue($mysqli, $setting) {
    $query = $mysqli->prepare("SELECT value FROM tbl_appconfig WHERE setting = ?");
    $query->bind_param("s", $setting);
    $query->execute();
    $result = $query->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row['value'];
    }
    return '';
}

// Fetch hotspot title and description from tbl_appconfig
$hotspotTitle = getSettingValue($mysqli, 'hotspot_title');
$description = getSettingValue($mysqli, 'description');
$phone = getSettingValue($mysqli, 'phone');
$company = getSettingValue($mysqli, 'CompanyName');

// Fetch settings
$settings = [];
$settings['frequently_asked_questions_headline1'] = getSettingValue($mysqli, 'frequently_asked_questions_headline1');
$settings['frequently_asked_questions_answer1'] = getSettingValue($mysqli, 'frequently_asked_questions_answer1');
$settings['frequently_asked_questions_headline2'] = getSettingValue($mysqli, 'frequently_asked_questions_headline2');
$settings['frequently_asked_questions_answer2'] = getSettingValue($mysqli, 'frequently_asked_questions_answer2');
$settings['frequently_asked_questions_headline3'] = getSettingValue($mysqli, 'frequently_asked_questions_headline3');
$settings['frequently_asked_questions_answer3'] = getSettingValue($mysqli, 'frequently_asked_questions_answer3');

// Fetch router name and router ID from tbl_appconfig
$routerName = getSettingValue($mysqli, 'router_name');
$routerId = getSettingValue($mysqli, 'router_id');

// Fetch available plans
$planQuery = "SELECT id, name_plan, price, validity, validity_unit FROM tbl_plans WHERE routers = ? AND type = 'Hotspot'";
$planStmt = $mysqli->prepare($planQuery);
$planStmt->bind_param("s", $routerName);
$planStmt->execute();
$planResult = $planStmt->get_result();

$htmlContent = "";
$htmlContent .= "<!DOCTYPE html>\n";
$htmlContent .= "<html lang=\"en\">\n";
$htmlContent .= "<head>\n";
$htmlContent .= "    <meta charset=\"UTF-8\">\n";
$htmlContent .= "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
$htmlContent .= "    <title>" . htmlspecialchars($company) . " Hotspot</title>\n";
$htmlContent .= "    <script src=\"https://cdn.tailwindcss.com\"></script>\n";
$htmlContent .= "    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css\">\n";
$htmlContent .= "    <link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/glider-js@1.7.7/glider.min.css\" />\n";
$htmlContent .= "    <script src=\"https://cdn.jsdelivr.net/npm/glider-js@1.7.7/glider.min.js\"></script>\n";
$htmlContent .= "    <link rel=\"preconnect\" href=\"https://cdn.jsdelivr.net\">\n";
$htmlContent .= "    <link rel=\"preconnect\" href=\"https://cdnjs.cloudflare.com\" crossorigin>\n";
$htmlContent .= "    <link rel=\"stylesheet\" href=\"https://rsms.me/inter/inter.css\">\n"; // Inter font
$htmlContent .= "    <link rel=\"stylesheet\" type=\"text/css\" href=\"styles.css\">\n";
$htmlContent .= "</head>\n";

$htmlContent .= "<body class=\"font-sans antialiased text-gray-900 bg-gray-900 font-inter\">\n"; // Apply Inter font
$htmlContent .= "    <!-- Main Content -->\n";$htmlContent .= "    <!-- Main Content -->\n";
$htmlContent .= "    <div class=\"flex flex-col items-center justify-center  p-4\">\n";

$htmlContent .= "        <div class=\"container mx-auto bg-gray-900 bg-opacity-100 rounded-lg p-6 mb-6\">\n";
$htmlContent .= "            <h3 class=\"text-3xl font-bold text-center text-white mb-4\">" . htmlspecialchars($company) . "</h3>\n";
$htmlContent .= "            <p class=\"text-xl text-center text-white mb-2\">(Customer Care): " . htmlspecialchars($phone) . "</p>\n";
$htmlContent .= "            <p class=\"text-sm text-center text-gray-300 mb-4\">\n";
$htmlContent .= "                Click Buy * Enter Mpesa No. * Enter pin * Wait to be connected\n";
$htmlContent .= "            </p>\n";
$htmlContent .= "<div class=\"flex justify-center\">\n";
$htmlContent .= "  <button class=\"bg-green bg-opacity-100 text-white font-bold py-1 px-2 rounded inline-flex items-center border border-white\">\n";
$htmlContent .= "    <svg class=\"w-5 h-5 mr-2\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\" xmlns=\"http://www.w3.org/2000/svg\">\n";
$htmlContent .= "      <path stroke-linecap=\"round\" stroke-linejoin=\"round\" stroke-width=\"2\" d=\"M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7\"></path>\n";
$htmlContent .= "    </svg>\n";
$htmlContent .= "    Redeem Voucher\n";
$htmlContent .= "  </button>\n";
$htmlContent .= "</div>\n";
$htmlContent .= "        </div>\n";
$htmlContent .= "<script>\n";
$htmlContent .= "function activateVoucher() {\n";
$htmlContent .= "    // Add your activation logic here\n";
$htmlContent .= "    alert('Voucher activated!');\n";
$htmlContent .= "}\n";
$htmlContent .= "</script>\n";

$htmlContent .= "        <div class=\"grid grid-cols-2 sm:grid-cols-3 gap-1 p-1\">\n";
while ($plan = $planResult->fetch_assoc()) {
   $htmlContent .= "            <div class=\"bg-white border border-black rounded-lg shadow-md overflow-hidden transition duration-300 hover:shadow-lg flex flex-col items-center justify-between mx-auto mb-4 w-40\">\n";
   $htmlContent .= "                <div class=\"bg-green-500 text-white w-full py-1 px-2\">\n";
   $htmlContent .= "                    <h2 class=\"text-sm font-medium uppercase text-center\" style=\"font-size: clamp(0.75rem, 1.5vw, 1rem); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;\">\n";
   $htmlContent .= "                        " . htmlspecialchars($plan['name_plan']) . "\n";
   $htmlContent .= "                    </h2>\n";
   $htmlContent .= "                </div>\n";
   $htmlContent .= "                <div class=\"px-4 py-2 flex-grow\">\n";
   $htmlContent .= "                    <p class=\"text-2xl font-bold text-green-600 mb-1\">\n";
   $htmlContent .= "                        <span class=\"text-lg font-medium text-black\">KSH</span>\n";
   $htmlContent .= "                        " . htmlspecialchars($plan['price']) . "\n";
   $htmlContent .= "                    </p>\n";
   $htmlContent .= "                    <p class=\"text-sm text-black mb-2\">\n";
   $htmlContent .= "                        Valid for " . htmlspecialchars($plan['validity']) . " " . htmlspecialchars($plan['validity_unit']) . " \n";
   $htmlContent .= "                    </p>\n";
   $htmlContent .= "                    <hr class=\"border-black mb-2\">\n";
   $htmlContent .= "                </div>\n";
   $htmlContent .= "                <div class=\"px-4 py-2 flex-shrink-0\">\n";
   $htmlContent .= "                    <a href=\"#\" class=\"inline-block bg-gray-900 text-white hover:bg-green-600 font-semibold py-1 px-4 rounded-lg transition duration-300 text-sm\"\n";
   $htmlContent .= "                       onclick=\"handlePhoneNumberSubmission(this.getAttribute('data-plan-id'), this.getAttribute('data-router-id')); return false;\"\n";
   $htmlContent .= "                       data-plan-id=\"" . $plan['id'] . "\"\n";
   $htmlContent .= "                       data-router-id=\"" . $routerId . "\">\n";
   $htmlContent .= "                        Subscribe\n";
   $htmlContent .= "                    </a>\n";
   $htmlContent .= "                </div>\n";
   $htmlContent .= "            </div>\n";
}
$htmlContent .= "        </div>\n";

$htmlContent .= "<div class=\"container mx-auto px-4\">\n";
$htmlContent .= "    <div class=\"max-w-md mx-auto bg-white rounded-lg overflow-hidden md:max-w-lg\">\n";
$htmlContent .= "        <div class=\"md:flex\">\n";
$htmlContent .= "            <div class=\"w-full p-5\">\n";
$htmlContent .= "                <div class=\"text-center\">\n";
$htmlContent .= "                    <h3 class=\"text-2xl text-gray-900\">Already Have an Active Package?</h3>\n";
$htmlContent .= "                </div>\n";
$htmlContent .= "                <form id=\"loginForm\" class=\"form\" name=\"login\" action=\"$(link-login-only)\" method=\"post\" $(if chap-id)onSubmit=\"return doLogin()\"$(endif)>\n";
$htmlContent .= "                    <input type=\"hidden\" name=\"dst\" value=\"$(link-orig)\" />\n";
$htmlContent .= "                    <input type=\"hidden\" name=\"popup\" value=\"true\" />\n";
$htmlContent .= "                    <div class=\"mb-4\">\n";
$htmlContent .= "                        <label class=\"block text-gray-700 text-sm font-bold mb-2\" for=\"username\">Enter mpesa number used to purchase package</label>\n";
$htmlContent .= "                        <div class=\"flex items-center\">\n";
$htmlContent .= "                            <input id=\"usernameInput\" class=\"shadow appearance-none border py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline\" name=\"username\" type=\"text\" value=\"\" placeholder=\"Username\" style=\"text-align: left;\">\n";
$htmlContent .= "                            <button id=\"submitBtn\" class=\"bg-green-500 hover:bg-blue-700 text-white font-bold py-2 px-4 focus:outline-none focus:shadow-outline ml-1\" type=\"button\">\n";
$htmlContent .= "                                Connect\n";
$htmlContent .= "                            </button>\n";
$htmlContent .= "                        </div>\n";
$htmlContent .= "                    </div>\n";
$htmlContent .= "                    <input type=\"hidden\" name=\"password\" value=\"1234\">\n";
$htmlContent .= "                </form>\n";
$htmlContent .= "            </div>\n";
$htmlContent .= "        </div>\n";
$htmlContent .= "    </div>\n";
$htmlContent .= "</div>\n";

// Slideshow container
$htmlContent .= "        <div class=\"container mx-auto bg-gray-900 bg-opacity-60 rounded-lg p-6 mb-6\">\n";
$htmlContent .= "            <div class=\"glider-contain\">\n";
$htmlContent .= "                <div class=\"glider\">\n";
$htmlContent .= "                    <div><img src=\"https://via.placeholder.com/600x300?text=Ad+1\" alt=\"Ad 1\" class=\"w-full h-auto rounded-lg\"></div>\n";
$htmlContent .= "                    <div><img src=\"https://via.placeholder.com/600x300?text=Ad+2\" alt=\"Ad 2\" class=\"w-full h-auto rounded-lg\"></div>\n";
$htmlContent .= "                    <div><img src=\"https://via.placeholder.com/600x300?text=Ad+3\" alt=\"Ad 3\" class=\"w-full h-auto rounded-lg\"></div>\n";
$htmlContent .= "                </div>\n";
$htmlContent .= "                <button aria-label=\"Previous\" class=\"glider-prev\">«</button>\n";
$htmlContent .= "                <button aria-label=\"Next\" class=\"glider-next\">»</button>\n";
$htmlContent .= "                <div role=\"tablist\" class=\"dots\"></div>\n";
$htmlContent .= "            </div>\n";
$htmlContent .= "        </div>\n";

$htmlContent .= "<script>\n";
$htmlContent .= "    document.addEventListener('DOMContentLoaded', function() {\n";
$htmlContent .= "        new Glider(document.querySelector('.glider'), {\n";
$htmlContent .= "            slidesToShow: 1,\n";
$htmlContent .= "            dots: '.dots',\n";
$htmlContent .= "            arrows: {\n";
$htmlContent .= "                prev: '.glider-prev',\n";
$htmlContent .= "                next: '.glider-next'\n";
$htmlContent .= "            }\n";
$htmlContent .= "        });\n";
$htmlContent .= "    });\n";
$htmlContent .= "</script>\n";

$htmlContent .= "<script>\n";
$htmlContent .= "document.addEventListener('DOMContentLoaded', function() {\n";
$htmlContent .= "    function autofillLogin() {\n";
$htmlContent .= "        var phoneNumber = '2547xxxxxxx';\n";
$htmlContent .= "        var password = '1234';\n";
$htmlContent .= "        document.querySelector('input[name=\"username\"]').value = phoneNumber;\n";
$htmlContent .= "        document.querySelector('input[name=\"password\"]').value = password;\n";
$htmlContent .= "        setTimeout(function() {\n";
$htmlContent .= "            document.querySelector('button[type=\"submit\"]').click();\n";
$htmlContent .= "        }, 15000);\n";
$htmlContent .= "    }\n";
$htmlContent .= "    autofillLogin();\n";
$htmlContent .= "});\n";
$htmlContent .= "</script>\n";


$htmlContent .= "<script src=\"https://cdn.jsdelivr.net/npm/sweetalert2@11\"></script>\n";

$htmlContent .= "<script>\n";
$htmlContent .= "    function formatPhoneNumber(phoneNumber) {\n";
$htmlContent .= "        if (phoneNumber.startsWith('+')) {\n";
$htmlContent .= "            phoneNumber = phoneNumber.substring(1);\n";
$htmlContent .= "        }\n";
$htmlContent .= "        if (phoneNumber.startsWith('0')) {\n";
$htmlContent .= "            phoneNumber = '254' + phoneNumber.substring(1);\n";
$htmlContent .= "        }\n";
$htmlContent .= "        if (phoneNumber.match(/^(7|1)/)) {\n";
$htmlContent .= "            phoneNumber = '254' + phoneNumber;\n";
$htmlContent .= "        }\n";
$htmlContent .= "        return phoneNumber;\n";
$htmlContent .= "    }\n";
$htmlContent .= "\n";
$htmlContent .= "    function handlePhoneNumberSubmission(planId, routerId) {\n";
$htmlContent .= "        Swal.fire({\n";
$htmlContent .= "            title: 'Enter Your Mpesa Number',\n";
$htmlContent .= "            input: 'text',\n";
$htmlContent .= "            inputAttributes: {\n";
$htmlContent .= "                autocapitalize: 'off'\n";
$htmlContent .= "            },\n";
$htmlContent .= "            showCancelButton: true,\n";
$htmlContent .= "            confirmButtonColor: '#3085d6',\n";
$htmlContent .= "            cancelButtonColor: '#d33',\n";
$htmlContent .= "            confirmButtonText: 'Submit',\n";
$htmlContent .= "            showLoaderOnConfirm: true,\n";
$htmlContent .= "            backdrop: `\n";
$htmlContent .= "                rgba(0,0,123,0.4)\n";
$htmlContent .= "                url(\"https://sweetalert2.github.io/images/nyan-cat.gif\")\n";
$htmlContent .= "                center left\n";
$htmlContent .= "                no-repeat\n";
$htmlContent .= "            `,\n";
$htmlContent .= "            preConfirm: (phoneNumber) => {\n";
$htmlContent .= "                var formattedPhoneNumber = formatPhoneNumber(phoneNumber);\n";
$htmlContent .= "                document.getElementById('usernameInput').value = formattedPhoneNumber;\n";
$htmlContent .= "                console.log(\"Phone number for autofill:\", formattedPhoneNumber);\n";
$htmlContent .= "\n";
$htmlContent .= "                return fetch('" . APP_URL . "/index.php?_route=plugin/CreateHotspotuser&type=grant', {\n";
$htmlContent .= "                    method: 'POST',\n";
$htmlContent .= "                    headers: {'Content-Type': 'application/json'},\n";
$htmlContent .= "                    body: JSON.stringify({phone_number: formattedPhoneNumber, plan_id: planId, router_id: routerId}),\n";
$htmlContent .= "                })\n";
$htmlContent .= "                .then(response => {\n";
$htmlContent .= "                    if (!response.ok) throw new Error('Network response was not ok');\n";
$htmlContent .= "                    return response.json();\n";
$htmlContent .= "                })\n";
$htmlContent .= "                .then(data => {\n";
$htmlContent .= "                    if (data.status === 'error') throw new Error(data.message);\n";
$htmlContent .= "                    Swal.fire({\n";
$htmlContent .= "    							icon: 'success',\n";
$htmlContent .= "                        title: 'Payment Request sent',\n";
$htmlContent .= "                        html: ` <b>\${formattedPhoneNumber}</b>.<br>A payment request has been sent to <b>\${formattedPhoneNumber}</b>. Dont click anything until you are connected.`,\n";
$htmlContent .= "                        timer: 25000, // Adjusted for 35 seconds\n";
$htmlContent .= "                        timerProgressBar: true,\n";
$htmlContent .= "                        didOpen: () => {\n";
$htmlContent .= "                            Swal.showLoading();\n";
$htmlContent .= "                            const timer = Swal.getPopup().querySelector(\"b\");\n";
$htmlContent .= "                            timerInterval = setInterval(() => {\n";
$htmlContent .= "                                timer.textContent = `\${Swal.getTimerLeft()}`;\n";
$htmlContent .= "                            }, 100);\n";
$htmlContent .= "                        },\n";
$htmlContent .= "                        willClose: () => {\n";
$htmlContent .= "                            clearInterval(timerInterval);\n";
$htmlContent .= "                        }\n";
$htmlContent .= "                    }).then((result) => {\n";
$htmlContent .= "                        if (result.dismiss === Swal.DismissReason.timer) {\n";
$htmlContent .= "                            console.log('I was closed by the timer');\n";
$htmlContent .= "                            document.getElementById('submitBtn').click();\n";
$htmlContent .= "                        }\n";
$htmlContent .= "                    });\n";
$htmlContent .= "                    return formattedPhoneNumber; \n";
$htmlContent .= "                })\n";
$htmlContent .= "                .catch(error => {\n";
$htmlContent .= "                    Swal.fire({\n";
$htmlContent .= "                        icon: 'error',\n";
$htmlContent .= "                        title: 'Oops...',\n";
$htmlContent .= "                        text: error.message,\n";
$htmlContent .= "                    });\n";
$htmlContent .= "                });\n";
$htmlContent .= "            },\n";
$htmlContent .= "            allowOutsideClick: () => !Swal.isLoading()\n";
$htmlContent .= "        });\n";
$htmlContent .= "    }\n";
$htmlContent .= "\n";
$htmlContent .= "    function FetchAjax(phoneNumber) {\n";
$htmlContent .= "        refreshData();\n";
$htmlContent .= "    }\n";
$htmlContent .= "\n";
$htmlContent .= "    function refreshData() {\n";
$htmlContent .= "        function refreshDataInternal() {\n";
$htmlContent .= "            $.ajax({\n";
$htmlContent .= "                url: '" . APP_URL . "/index.php?_route=plugin/CreateHotspotuser&type=verify',\n";
$htmlContent .= "                method: \"POST\",\n";
$htmlContent .= "                data: {phone_number: document.getElementById('usernameInput').value},\n";
$htmlContent .= "                dataType: \"json\",\n";
$htmlContent .= "                success: function(data) {\n";
$htmlContent .= "                    // Response handling code\n";
$htmlContent .= "                },\n";
$htmlContent .= "                error: function(xhr, textStatus, errorThrown) {\n";
$htmlContent .= "                    console.log(\"Error: \" + errorThrown);\n";
$htmlContent .= "                }\n";
$htmlContent .= "            });\n";
$htmlContent .= "        }\n";
$htmlContent .= "        var refreshInterval = setInterval(refreshDataInternal, 2000);\n";
$htmlContent .= "    }\n";
$htmlContent .= "\n";
$htmlContent .= "    document.addEventListener('DOMContentLoaded', function() {\n";
$htmlContent .= "        var submitBtn = document.getElementById('submitBtn');\n";
$htmlContent .= "        if (submitBtn) {\n";
$htmlContent .= "            submitBtn.addEventListener('click', function(event) {\n";
$htmlContent .= "                event.preventDefault();\n";
$htmlContent .= "                document.getElementById('loginForm').submit();\n";
$htmlContent .= "            });\n";
$htmlContent .= "        }\n";
$htmlContent .= "    });\n";
$htmlContent .= "</script>\n";



$htmlContent .= "<script src=\"https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js\"></script>\n";

$htmlContent .= "<script>\n";
$htmlContent .= "document.addEventListener('DOMContentLoaded', function() {\n";
$htmlContent .= "     // Ensure the button is correctly targeted by its ID.\n";
$htmlContent .= "     var submitBtn = document.getElementById('submitBtn');\n";
$htmlContent .= "     \n";
$htmlContent .= "     // Add a click event listener to the \"Login Now\" button.\n";
$htmlContent .= "     submitBtn.addEventListener('click', function(event) {\n";
$htmlContent .= "         event.preventDefault(); // Prevent the default button action.\n";
$htmlContent .= "         \n";
$htmlContent .= "         // Optional: Log to console for debugging purposes.\n";
$htmlContent .= "         console.log(\"Login Now button clicked.\");\n";
$htmlContent .= " \n";
$htmlContent .= "         // Direct form submission, bypassing the doLogin function for simplicity.\n";
$htmlContent .= "         var form = document.getElementById('loginForm');\n";
$htmlContent .= "         form.submit(); // Submit the form directly.\n";
$htmlContent .= "     });\n";
$htmlContent .= "});\n";
$htmlContent .= "</script>\n";


$htmlContent .= "</html>\n";







$planStmt->close();
$mysqli->close();
// Check if the download parameter is set
if (isset($_GET['download']) && $_GET['download'] == '1') {
   // Prepare the HTML content for download
   // ... build your HTML content ...

   // Specify the filename for the download
   $filename = "login.html";

   // Send headers to force download
   header('Content-Type: application/octet-stream');
   header('Content-Disposition: attachment; filename='.basename($filename));
   header('Expires: 0');
   header('Cache-Control: must-revalidate');
   header('Pragma: public');
   header('Content-Length: ' . strlen($htmlContent));

   // Output the content
   echo $htmlContent;

   // Prevent any further output
   exit;
}

// Regular page content goes here
// ... HTML and PHP code to display the page ...


