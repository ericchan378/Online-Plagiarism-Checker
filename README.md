# Online-Plagiarism-Checker
Checks for plagiarism in a block of text against the internet

Developed alongside being familiar with UML diagrams, various testing practices, and overall SDLC

PHP script takes user input and processes it to send through Google search API.
JSON data from the API request is parsed and outputs a list of web pages ranked by the percent match between the user inputted text and text found on the web page.

Originally the idea was to use my own search engine instead of the Google Search API but would require me to index a significant portion of the internet.
Search queries to the MySQL database also took a long time and many queries would be required.

Testing was done using XAMPP. Files are placed in C:\xampp\htdocs...
HTML file is presented to the user to either enter text or choose text file.
