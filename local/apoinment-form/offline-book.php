<script>
  function getQueryParams() {
    const params = new URLSearchParams(window.location.search);
    return {
      itemId: params.get("itemId"),
      mode: params.get("mode"),
      time: params.get("time"),
      locationOff: params.get("location"),
    };
  }

  const { itemId, mode, time, locationOff } = getQueryParams();

  if (itemId && mode && time && locationOff) {
    const webhookUrl = "https://primocapitalcrm.ae/rest/4780/ua3qos1ab1cp7lc9/crm.item.update";
    
    const myHeaders = new Headers();
    myHeaders.append("Content-Type", "application/json");

    const raw = JSON.stringify({
      entityTypeId: 1032,
      id: itemId,
      fields: {
        ufCrm4_1738559409423: time,
        ufCrm4_1739514453606: "1052",
        ufCrm4_1741242761540: locationOff,
      }
    });

    const requestOptions = {
      method: "POST",
      headers: myHeaders,
      body: raw,
      redirect: "follow"
    };

    fetch(webhookUrl, requestOptions)
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.text();
      })
      .then(result => {
        console.log("Success:", result);
        window.location.href = "https://primocapitalcrm.ae/local/apoinment-form/thank.php";
      })
      .catch(error => {
        console.error("API Error:", error);
        alert("There was an error updating the record. Please try again.");
      });
  } else {
    console.log("Missing parameters in the URL.");
  }
</script>
