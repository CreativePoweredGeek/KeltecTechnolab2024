{exp:formgrab:form
    name="contact_form"
    title="Contact Form"
}

<div class="row">
	<div class="form-group col-md-6">
	    <label for="name">Full Name</label>
	    <input type="text" class="form-control" name="name" id="name" placeholder="Full Name">
	</div>
	<div class="form-group col-md-6">
	    <label for="company">Company</label>
	    <input type="text" class="form-control" name="company" id="company" placeholder="Company">
	</div>
</div>
<div class="row">
	<div class="form-group col-md-6">
	    <label for="email">Email address</label>
	    <input type="email" class="form-control" name="email" id="email" aria-describedby="emailHelp" placeholder="Enter email">
	    <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
	</div>
	<div class="form-group col-md-6">
	    <label for="phone">Phone</label>
	    <input type="tel" class="form-control" name="phone" id="phone" aria-describedby="emailHelp" placeholder="Phone Number">
	</div>
</div>
<div class="form-group">
    <label for="subject">Subject</label>
    <input type="text" class="form-control" name="subject" id="subject" placeholder="Subject">
</div>
<div class="form-group">
    <label for="message">Message</label>
    <textarea class="form-control" id="message" name="message" rows="6"></textarea>
</div>



<button type="submit" class="btn btn-primary">Submit</button>

{/exp:formgrab:form}