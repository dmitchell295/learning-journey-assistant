# Learning Journey Assistant
## Limitations, Future Work and Conclusion

## 1. Current Limitations

### 1.1 Rubric and Learning Outcome Mapping

The current system supports learning outcomes and rubric-related information, but the complete mapping between rubric criteria, learning outcomes and competencies requires further validation.

A formally validated mapping would improve the reliability of competency-level learning insights.

### 1.2 Mastery Estimation

The project includes AI-assisted mastery estimation and categorical mastery information.

However, the final mastery calculation requires further validation before it can be considered suitable for formal academic assessment.

The current mastery categories are:

- Achieved
- Partially Achieved
- Not Yet Achieved

These should be treated as formative indicators rather than official academic grades.

### 1.3 Test and Synthetic Data

Some development and testing activities use synthetic or test data.

Further validation using appropriately authorised real Moodle data would be required before the system could be used in a production academic environment.

### 1.4 Student Data and Privacy

The system processes student-related academic information.

A production implementation would require appropriate authentication, authorisation, privacy controls and university approval before accessing real student information.

### 1.5 Deployment

The system has been developed as an integrated backend and frontend application, but additional deployment and operational testing would be required before production use.

This includes secure hosting, configuration, monitoring and reliability testing.

## 2. Future Work

### 2.1 Improve Mastery Estimation

Future work should establish a formally validated method for combining grades, rubric results, feedback and learning outcomes.

The AI component could then provide more reliable evidence-based formative learning insights.

### 2.2 Strengthen Competency Mapping

Future development should establish a complete and validated relationship between:

Learning Outcomes → Rubric Criteria → Competencies

This would allow the system to provide more meaningful competency-level progress information.

### 2.3 Longitudinal Student Progress

The system could be extended to track student progress across multiple assignments and subjects over time.

This would allow students to identify whether their strengths and learning gaps are improving.

### 2.4 Personalised Learning Recommendations

Future versions could provide targeted recommendations based on identified learning gaps.

Possible recommendations include:

- learning resources
- practice activities
- revision topics
- targeted study strategies

### 2.5 Production Deployment

Future work should include deployment of the complete application in a secure production environment.

This should include:

- secure authentication
- access control
- privacy protection
- monitoring
- backup and recovery
- security testing
- performance testing

## 3. Ethical and Privacy Considerations

The Learning Journey Assistant works with academic information that may be sensitive.

Future production deployment should ensure that student information is accessed only by authorised users and handled according to applicable university privacy and security requirements.

AI-generated mastery information should remain formative and should not replace official academic assessment.

The system should also provide transparency about how learning insights are generated.

## 4. Conclusion

The Learning Journey Assistant provides a foundation for personalised academic learning support by connecting academic information, backend processing, AI-assisted analysis and a student-facing dashboard.

The project progressed through an iterative Scrum process, moving from initial system design and data modelling through backend API development, Moodle integration, AI processing, frontend integration and testing.

The final integration testing demonstrated that the major system components can communicate and perform their intended functions.

The project also identified areas requiring further development, particularly mastery calculation, competency mapping, production deployment and validation using real authorised academic data.

Overall, the project demonstrates the feasibility of using integrated academic data and AI-assisted analysis to help students understand their strengths, learning gaps and current level of understanding.

The current system should be considered a functional foundation for further development rather than a final production-ready academic assessment system.
