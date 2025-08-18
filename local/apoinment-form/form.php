<?php
$calender_id = isset($_GET['calender_id']) ? $_GET['calender_id'] : null;
$itemId = isset($_GET['itemId']) ? $_GET['itemId'] : null;
$nameOfCandidate = isset($_GET['nameOfCandidate']) ? $_GET['nameOfCandidate'] : "Unknown Candidate";
$emailOfCandidate = isset($_GET['emailOfCandidate']) ? $_GET['emailOfCandidate'] : "Unknown Candidate";
$nameOfintrwivar = isset($_GET['nameOfintrwivar']) ? $_GET['nameOfintrwivar'] : "Unknown Interviewer";
$observers = isset($_GET['observers']) ? $_GET['observers'] : "Unknown observers";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Interview Scheduler</title>

    <!-- Bootstrap -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Flatpickr DateTime Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">

    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            background-color: #f8f9fa;
            margin: 0;
            padding: 20px;
        }

        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 400px;
        }

        .btn-primary {
            width: 100%;
            margin-top: 15px;
            background-color: #28a745;
            border-color: #28a745;
        }

        .btn-primary:hover {
            background-color: #218838;
            border-color: #1e7e34;
        }

        .form-label {
            font-weight: bold;
        }

        #datetimediv {
            display: none;
        }
       #locationdiv {
            display: none;
        }

        /* Flatpickr Time Picker Styling */
        .flatpickr-time {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 10px;
            gap: 10px;
        }

        .flatpickr-hour, .flatpickr-minute {
            background-color: #2196f3 !important; 
            color: white !important;
            font-size: 18px;
            border-radius: 8px;
            padding: 10px;
            width: 60px;
            text-align: center;
        }

        /* AM/PM Button Styling */
        .flatpickr-am-pm {
            font-weight: bold;
            padding: 10px;
            font-size: 18px;
            width: 60px;
            text-align: center;
        }

        .flatpickr-am-pm[data-value="AM"] {
            background-color: #FFC107 !important; /* Blue */
            color: white !important;
        }

        .flatpickr-am-pm[data-value="PM"] {
            background-color: #212121 !important; /* Green */
            color: white !important;
        }

        /* OK & Cancel Buttons in a New Row */
        .flatpickr-time-btns {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            padding: 10px 20px;
        }

        .flatpickr-cancel, .flatpickr-ok {
            padding: 10px;
            font-size: 16px;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 45%;
            text-align: center;
        }

        .flatpickr-cancel {
            background-color: #dc3545;
        }

        .flatpickr-ok {
            background-color: #007bff;
        }
    </style>
</head>
<body>
    <div class="container">
        <h3 class="text-center">Interview Scheduler</h3>
        <form id="appointmentForm" method="post">
            
            <!-- Candidate Name (Read-Only) -->
            <div class="mb-3">
                <label class="form-label">Candidate Name:</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($nameOfCandidate); ?>" readonly>
            </div>

            <!-- Interviewer Name (Read-Only) -->
            <div class="mb-3">
                <label class="form-label">Interviewer Name:</label>
                <input type="text" class="form-control" value="<?php echo htmlspecialchars($nameOfintrwivar); ?>" readonly>
            </div>

            <!-- Mode Selection -->
            <div class="mb-3">
                <label for="mode" class="form-label">Select Mode:</label>
                <select id="mode" name="mode" class="form-control" onchange="handleModeChange(this)" required>
                    <option value="">Select Mode</option>
                    <option value="offline">Offline</option>
                    <option value="online">Online</option>
                </select>
            </div>

            <!-- Date & Time Picker -->
            <div class="mb-3 dateClass" id="datetimediv">
                <label for="datetimepicker" class="form-label">Select Date & Time:</label>
                <input type="text" id="datetimepicker" name="datetime" class="form-control" placeholder="Select Date & Time" required>
            </div>
             <div class="mb-3 locationclass" id="locationdiv">
                <label for="location" class="form-label">Location for offline interview:</label>
                <input type="text" id="location" name="location" class="form-control" placeholder="Enter Location"required>
            </div>

            <!-- Submit Button -->
            <button type="submit" class="btn btn-primary">Schedule Interview</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Flatpickr DateTime Picker -->
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

    <script>
        function handleModeChange(selectValue) {
            let modeValue = selectValue.value;
            const dateTimeField = document.getElementById('datetimediv');
            const locationField = document.getElementById('locationdiv');
           const locationInput = document.getElementById("location");
            dateTimeField.style.display = (modeValue === "online") ? "none" : "block";
            locationField.style.display = (modeValue === "online") ? "none" : "block";
			if(modeValue==="online"){
             locationInput.removeAttribute("required");
			}else if(modeValue==="offline"){
             locationInput.setAttribute("required", true);
			}

        }

        // Initialize DateTime Picker with Custom Buttons
        flatpickr("#datetimepicker", {
            enableTime: true,
            noCalendar: false,
            dateFormat: "Y-m-d h:i K",
            time_24hr: false,
            disableMobile: true,

            onReady: function(selectedDates, dateStr, instance) {
                const fp = instance;
                setTimeout(() => {
                    let amPmElement = fp.calendarContainer.querySelector(".flatpickr-am-pm");
                    function updateAmPmColor() {
                        amPmElement.dataset.value = amPmElement.innerText.trim();
                    }
                    amPmElement.addEventListener("click", updateAmPmColor);
                    updateAmPmColor();

                    let btnContainer = document.createElement("div");
                    btnContainer.classList.add("flatpickr-time-btns");

                    let cancelButton = document.createElement("button");
                    cancelButton.innerText = "Cancel";
                    cancelButton.classList.add("flatpickr-cancel");
                    cancelButton.addEventListener("click", function () {
                        fp.close();
                    });

                    let okButton = document.createElement("button");
                    okButton.innerText = "OK";
                    okButton.classList.add("flatpickr-ok");
                    okButton.addEventListener("click", function () {
                        if (fp.selectedDates.length > 0) {
                            fp.close();
                        }
                    });

                    btnContainer.appendChild(cancelButton);
                    btnContainer.appendChild(okButton);

                    let parentContainer = fp.calendarContainer.querySelector(".flatpickr-time").parentElement;
                    parentContainer.appendChild(btnContainer);
                }, 10);
            }
        });
			document.getElementById('appointmentForm').addEventListener('submit', function(event) {
				event.preventDefault(); 
			
				let modeValue = document.getElementById("mode").value;
			  let dateTimeValue = document.getElementById("datetimepicker")?.value || "";
               let locationValue = document.getElementById("location")?.value || "";

				var calender_id = "<?php echo htmlspecialchars($calender_id); ?>";
				var itemId = "<?php echo htmlspecialchars($itemId); ?>";
                var name = "<?php echo htmlspecialchars($nameOfCandidate); ?>";
				var email = "<?php echo htmlspecialchars($emailOfCandidate); ?>";
                var observers = "<?php echo htmlspecialchars($observers); ?>";
			
				let redirectUrl = "";
			
				if (modeValue === "online") {
			   redirectUrl = "https://primocapitalcrm.ae/pub/calendar-sharing/" + calender_id + "/&item=" + itemId+"&name="+name+"&email="+email+"&observers="+observers;
				} else if (modeValue === "offline") {
			
					redirectUrl = "https://primocapitalcrm.ae/local/apoinment-form/offline-book.php?itemId="+itemId+"&mode="+modeValue+"&time="+dateTimeValue+"&location="+locationValue; // Replace with actual offline form URL
				} else {
					alert("Please select a mode before submitting.");
					return; // Stop form submission if no valid mode is selected
				}
			
				// Redirect the user
				window.location.href = redirectUrl;
			});


    </script>
</body>
</html>
