(function ($, Drupal) {
	"use strict";
	Drupal.behaviors.vactory_suggest_password = {
		attach: function (context, settings) {
			var pwd = "";
			$("#edit-pass-pass1").on("focus", function () {
				showSuggestion($(this))
			});

			$("#edit-pass-pass1").on("input", function () {
				var value = $(this).val()
				if (value === '') {
					showSuggestion($(this))
				}
			});

			var suggested_password = "<div class='pwd-suggestion d-none border rounded border-primary p-2 small text-muted'>Get new password: <a class='btn-pwd'></a><br>" + Drupal.t("Password will be copied") + "</div>"
			$("#edit-pass-pass1").after(suggested_password)

			$("body").on("click", ".btn-pwd", function (e) {
				e.preventDefault()
				$("#edit-pass-pass1").val(pwd);
				$("#edit-pass-pass2").val(pwd);
				copyPwd();
				$("#edit-pass-pass1").trigger("input");
				$(".pwd-suggestion").hide();
			});

			function showSuggestion(element) {
				pwd = generatePassword();
				$(".btn-pwd").text(pwd).css("cursor" , "pointer");
				var value = element.val()
				if (value === '') {
					$(".pwd-suggestion").removeClass('d-none').show();
				}
			}

			//Creating password object.
			var pwdCriteria = {

				//Property for length of password
				pwdLength: 0,

				//array to hold lowercase letters
				pwdLowerCase: ["a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k", "l",
					"m", "n", "o", "p", "q", "r", "s", "t", "u", "v", "w", "x", "y", "z"],

				//array to hold uppercase letters
				pwdUpperCase: ["A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L",
					"M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "W", "X", "Y", "Z"],

				//array to hold numbers
				pwdNumber: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],

				//array to hold special characters
				pwdCharacter: ["!", "\"", "#", "$", "%", "&", "\'", "(", ")", "*", "+", ",",
					"-", ".", "/", "\\", ":", ";", "<", ">", "=", "?", "@", "[", "]", "^", "_", "`", "{", "}", "|", "~"]//32
			};

			//function to handle the operations to generate a new password
			function generatePassword() {
				//holds the password to be generated and returned
				var result = "";

				//variables to collect input from user prompts
				var passwordLength = 0;
				var lowerCase = 1;
				var upperCase = 1;
				var numbers = 1;
				var specialChar = 1;

				//initialize characters
				passwordLength = 0;
				pwdCriteria.pwdLength = 0;
				result = "";

				//passwordLength = drupalSettings['password_suggestion']['length'];
				passwordLength = 12;
				//if user presses cancel
				if (passwordLength === null) {
					return "Your secure password";
				}
				else {
					//checking user enters an integer
					if (!isFinite(passwordLength)) {
						alert("You did not enter a number");
						return "Your secure password";
					}
					else {
						//check password meets length criteria
						if (passwordLength < 8 || passwordLength > 128) {
							alert("Password must be between 8 and 128 characters.");
							return "Your secure password";
						}
						else {
							//keep adding characters based on criteria until pwdLength is = to the length the user set
							while (pwdCriteria.pwdLength < passwordLength) {
								//if statement to make sure the user selects at least one of the criteria
								if (lowerCase === 0 && upperCase === 0 && numbers === 0 && specialChar === 0) {
									alert("You must select at least one criteria of lowercase, uppercase, numbers or special characters");
								}
								else {
									//if the user selected lowercase and there is still room to add characters then
									//randomly grab a lowercase letter from the array and add it to the end of result
									//update pwdLength by 1
									if (lowerCase === 1 && pwdCriteria.pwdLength < passwordLength) {
										var lc = pwdCriteria.pwdLowerCase[Math.floor(Math.random() * 26)];
										result = result + lc;
										pwdCriteria.pwdLength++;
									}

									//if the user selected a special character and there is still room to add characters then
									//randomly grab a apecial character from the array and add it to the end of result
									//update pwdLength by 1
									if (specialChar === 1 && pwdCriteria.pwdLength < passwordLength) {
										var sc = pwdCriteria.pwdCharacter[Math.floor(Math.random() * 32)];
										result = result + sc;
										pwdCriteria.pwdLength++;
									}

									//if the user selected an uppercase letter and there is still room to add characters then
									//randomly grab an uppercase letter from the array and add it to the end of result
									//update pwdLength by 1
									if (upperCase === 1 && pwdCriteria.pwdLength < passwordLength) {
										var uc = pwdCriteria.pwdUpperCase[Math.floor(Math.random() * 26)];
										result = result + uc;
										pwdCriteria.pwdLength++;
									}

									//if the user selected a number and there is still room to add characters then
									//randomly grab a number from the array and add it to the end of result
									//update pwdLength by 1
									if (numbers === 1 && pwdCriteria.pwdLength < passwordLength) {
										var num = pwdCriteria.pwdNumber[Math.floor(Math.random() * 10)];
										result = result + num;
										pwdCriteria.pwdLength++;
									}
								}
							}
						}
					}
				}
				//return the generated password back to the calling function
				return result;
			}

			// Copy to clipboard
			function copyPwd() {
				var $temp = $("<input>");
				$("body").append($temp);
				$temp.val(pwd).select();
				document.execCommand("copy");
				$temp.remove();
			}
		}
	}

})(jQuery, Drupal);
