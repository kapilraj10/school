# You are provided with a list of tasks. Execute all tasks completely.

# Before writing any code:
Scan the entire codebase to identify existing implementations, utilities, services, models, migrations, or helpers.
Reuse existing code wherever applicable instead of duplicating functionality.
Review all related database tables and schemas before making any schema or data changes.

# Code generation rules:
Do not create documentation files.
Do not add inline comments or block comments inside the code.
Generate code that is maintainable, scalable, and high-performance.
Follow existing project architecture, naming conventions, and patterns.
Avoid unnecessary abstractions, but ensure clean separation of concerns.
Ensure changes are backward-compatible unless explicitly required otherwise.

# Validation:
Ensure all tasks are fully completed.
Ensure the application builds and runs without errors after changes.
Do not leave partial implementations or TODOs.

# http://127.0.0.1:8000/


# http://127.0.0.1:8000/admin
- make the search button at the nav bar functional

# http://127.0.0.1:8000/admin/timetable-settings

# http://127.0.0.1:8000/admin/class-subject-settings

# http://127.0.0.1:8000/admin/subjects

# http://127.0.0.1:8000/admin/timetable-generator?step=review-generate
- show interaction like running class1A , completed class 1A
- the timetable generation doesnot stops even after completing generation
- replace loading indicator with progress bar

# http://127.0.0.1:8000/timetable-designer
- in subject list, subjects count should be displayed with their maximum period per week.
- moving subject from subject list to timetable cell should decrease the subject count and vice versa
- if subject count reaches 0 then dont display that subject.
- use Tailwind token: gray-950 color background for dark theme of timetable designer page.
- update the color for core subject and co curricular subject to perfectly match dark theme and light theme background change.

# http://127.0.0.1:8000/admin/conflict-checker

# http://127.0.0.1:8000/admin/teachers/teachers
- clicking teacher should display a table showing teacher availability as in the uploaded image 
- also change create and edit operation for available periods and available days by adding a table to select yes or no in each perticular cell

# http://127.0.0.1:8000/admin/teachers/teachers
- change place holder for nepali phone number and add +977 as prefix. 
- while creating teacher remove status field and by defult set status active.
- place class assignment before subject assignment.
- after user selects class for example class 1 then only show class 1 subjects only in subject assignment.

- timetable day wise