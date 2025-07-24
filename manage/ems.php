<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ODK Enketo Form</title>
</head>
<body>
    <iframe id="enketoForm" src="https://staging.enketo.getodk.org/preview?form=https%3A//staging.xlsform.getodk.org/downloads/1cae4f22026f4a6aa61a30294948e76b0xaacu6b/Ebola.xml" 
        width="100%" height="800px" style="border: none;"></iframe>

    <script>
        window.addEventListener("message", function(event) {
            if (event.origin !== "https://staging.enketo.getodk.org") return;

            const data = event.data;
            if (data && data.instanceId) {
                console.log("Instance ID:", data.instanceId);
                
                // Send instance ID to PHP backend
                fetch("ems_action.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({ instanceId: data.instanceId })
                }).then(response => response.json())
                  .then(data => console.log("Server response:", data))
                  .catch(error => console.error("Error:", error));
            }
        }, false);
    </script>
</body>
</html>
