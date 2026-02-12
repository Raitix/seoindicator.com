const chai = require('chai');
const chaiHttp = require('chai-http');
const app = require('../app'); // Assuming app.js is the entry point of your server
chai.use(chaiHttp);
const expect = chai.expect;

describe('Meta Description AI Analysis', () => {
  it('should return AI analysis for a valid meta description', (done) => {
    chai.request(app)
      .post('/api/analyze/meta-description')
      .send({ metaDescription: 'This is a sample meta description for testing.' })
      .end((err, res) => {
        expect(res).to.have.status(200);
        expect(res.body).to.have.property('analysis');
        expect(res.body.analysis).to.be.a('string');
        done();
      });
  });

  it('should return an error for an empty meta description', (done) => {
    chai.request(app)
      .post('/api/analyze/meta-description')
      .send({ metaDescription: '' })
      .end((err, res) => {
        expect(res).to.have.status(400);
        expect(res.body).to.have.property('error');
        expect(res.body.error).to.equal('Meta description cannot be empty.');
        done();
      });
  });

  it('should return an error for a missing meta description field', (done) => {
    chai.request(app)
      .post('/api/analyze/meta-description')
      .send({})
      .end((err, res) => {
        expect(res).to.have.status(400);
        expect(res.body).to.have.property('error');
        expect(res.body.error).to.equal('Meta description is required.');
        done();
      });
  });
});