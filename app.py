from flask import Flask
from flask_cors import CORS
from models import db, Subject

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:///learning_journey.db'
CORS(app)
db.init_app(app)

with app.app_context():
    db.create_all()

@app.route("/")
def home():
    return {"message": "Learning Journey Assistant API is running"}

@app.route("/subjects")
def get_subjects():
    subjects = Subject.query.all()
    return {"subjects": [s.name for s in subjects]}

if __name__ == "__main__":
    app.run(debug=True, port=5000)