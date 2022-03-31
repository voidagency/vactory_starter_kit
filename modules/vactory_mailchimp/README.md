**API**
----
Create a new subscriber

* **URL**

  /fr/_mailchimp

* **Method:**

  `POST`

*  **URL Params**

   **Required:**

   `id=[string]`
   `email=[string]`


* **Success Response:**

  * **Code:** 201 <br />
    **Content:** `{
    "status": "success",
    "email": "test@example.com",
    "messages": "You have successfully subscribed"
    }`

